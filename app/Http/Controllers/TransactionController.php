<?php

namespace App\Http\Controllers;

use App\Events\NewMidtransTransaction;
use App\Events\SelfOrderCashPendingCreated;
use App\Events\SelfOrderPaymentUpdated;
use App\Http\Requests\HandleSelfOrderPaymentRequest;
use App\Models\DiningTable;
use App\Models\Member;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\VoucherCode;
use App\Models\VoucherRedemption;
use App\Services\PriceService;
use App\Services\SelfOrder\SelfOrderCheckoutService;
use App\Services\Transactions\ReceiptEmailService;
use App\Services\Transactions\TransactionEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap as MidtransSnap;

class TransactionController extends Controller
{
    public function __construct()
    {
        MidtransConfig::$serverKey = (string) config('midtrans.server_key');
        MidtransConfig::$isProduction = (bool) config('midtrans.is_production');
        MidtransConfig::$isSanitized = (bool) config('midtrans.is_sanitized');
        MidtransConfig::$is3ds = (bool) config('midtrans.is_3ds');
    }

    public function handlePayment(HandleSelfOrderPaymentRequest $request)
    {
        $validated = $request->validated();
        $action = (string) $validated['action'];

        $sessionToken = session('payment_token');
        $requestToken = (string) $validated['token'];
        if (! is_string($sessionToken) || ! hash_equals($sessionToken, $requestToken)) {
            return redirect()->route('self-order.payment.failure');
        }

        if (array_key_exists('voucher_code', $validated)) {
            $code = trim((string) ($validated['voucher_code'] ?? ''));
            session(['self_order_voucher_code' => $code !== '' ? $code : null]);
        }

        if (array_key_exists('use_points', $validated) || array_key_exists('points_to_redeem', $validated)) {
            session([
                'self_order_use_points' => (bool) ($validated['use_points'] ?? false),
                'self_order_points_to_redeem' => (int) ($validated['points_to_redeem'] ?? 0),
            ]);
        }

        if ($action === 'pay') {
            $method = (string) ($validated['method'] ?? 'online');
            if ($method === 'cashier') {
                return $this->processCashierPayment($sessionToken);
            }
            $enabled = (bool) (Setting::current()->payment_gateway_enabled ?? true);
            if (! $enabled) {
                return $this->processCashierPayment($sessionToken);
            }

            return $this->processPayment($sessionToken);
        }

        if ($action === 'continue') {
            $externalId = session('external_id');

            if (empty($externalId)) {
                return view('livewire.self-order.payment.failure');
            }

            $transaction = Transaction::where('external_id', $externalId)->first();
            if (! $transaction || empty($transaction->checkout_link) || $transaction->checkout_link === '-') {
                return view('livewire.self-order.payment.failure');
            }

            return redirect($transaction->checkout_link);
        }

        abort(400, 'Invalid action.');
    }

    public function midtransUnprocessed(): JsonResponse
    {
        $user = auth()->user();
        if (! $user || ! $user->can('transactions.view')) {
            abort(403);
        }

        $canViewDetail = $user->can('transactions.details');

        $items = Transaction::query()
            ->where('channel', 'self_order')
            ->where('payment_method', 'qris_midtrans')
            ->where('payment_status', 'paid')
            ->where('is_midtrans_processed', false)
            ->with('diningTable:id,table_number')
            ->latest('paid_at')
            ->limit(15)
            ->get(['id', 'code', 'total', 'dining_table_id', 'paid_at', 'created_at'])
            ->map(fn (Transaction $t) => [
                'id' => (int) $t->id,
                'code' => (string) $t->code,
                'total' => (int) $t->total,
                'total_formatted' => 'Rp '.number_format((int) $t->total, 0, ',', '.'),
                'table' => (string) ($t->diningTable?->table_number ?? ''),
                'paid_at' => $t->paid_at?->format('d M Y, H:i'),
                'detail_url' => $canViewDetail ? route('transactions.show', (int) $t->id, false) : null,
            ])
            ->all();

        return response()->json([
            'count' => Transaction::query()
                ->where('channel', 'self_order')
                ->where('payment_method', 'qris_midtrans')
                ->where('payment_status', 'paid')
                ->where('is_midtrans_processed', false)
                ->count(),
            'items' => $items,
        ]);
    }

    public function processCashierPayment(string $sessionToken)
    {
        $cartItems = session('cart_items');

        if (! is_array($cartItems)) {
            $cartItems = [];
        }

        $checkout = app(SelfOrderCheckoutService::class);
        $result = $checkout->validateAndHydrateCartItems($cartItems);
        if ($result['changed']) {
            session(['cart_items' => $result['items']]);

            return redirect()->route('self-order.payment.cart')->with('error', 'Beberapa item tidak tersedia dan telah dihapus dari keranjang.');
        }

        $cartItems = $result['items'];
        $variants = $result['variants'];

        $name = session('name');
        $phone = session('phone');
        $email = session('email');
        $memberId = session('member_id');
        $tableId = session('dining_table_id');

        if (empty($cartItems) || empty($name) || empty($phone) || empty($tableId)) {
            return redirect()
                ->route('self-order.payment.cart')
                ->with('error', 'Data pemesanan belum lengkap.');
        }

        $table = DiningTable::find($tableId);
        if (! $table) {
            return redirect()
                ->route('self-order.invalid')
                ->with('error', 'Meja tidak valid.');
        }

        $transactionCode = Transaction::generateUniqueCode();

        try {
            $cartHash = $checkout->cartHash($cartItems);
            $paymentSessionHash = hash('sha256', (string) session('self_order_token').'|'.$sessionToken);
            $paymentIntentHash = hash('sha256', (string) session('self_order_token').'|'.$paymentSessionHash.'|'.$cartHash.'|cashier');

            $existing = Transaction::query()
                ->where('payment_intent_hash', $paymentIntentHash)
                ->first();
            if ($existing) {
                return redirect()->route('self-order.payment.status', ['code' => $existing->code]);
            }

            $grossSubtotal = 0;
            foreach ($cartItems as $item) {
                $price = (isset($item['price_afterdiscount']) && (int) $item['price_afterdiscount'] > 0 && (int) $item['price_afterdiscount'] < (int) $item['price']) ? (int) $item['price_afterdiscount'] : (int) $item['price'];
                $grossSubtotal += $price * ($item['quantity'] ?? 1);
            }

            $voucher = $this->resolveVoucherForCart($cartItems, $memberId, (string) $phone);
            if (! $voucher['ok']) {
                return redirect()
                    ->route('self-order.payment.page')
                    ->with('error', (string) $voucher['message']);
            }

            $voucherDiscountAmount = (int) $voucher['voucher_discount_amount'];
            $netSubtotal = max(0, (int) $grossSubtotal - $voucherDiscountAmount);

            $setting = Setting::current();
            $taxRate = (float) ($setting->tax_rate ?? 0);
            $pointsToRedeem = 0;
            $pointDiscountAmount = 0;

            if ((bool) session('self_order_use_points') && (string) session('customer_type') === 'member' && is_numeric($memberId)) {
                $member = Member::query()->find((int) $memberId);
                $availablePoints = (int) ($member?->points ?? 0);
                $desiredPoints = max(0, (int) session('self_order_points_to_redeem', 0));

                $minRedemptionPoints = (int) ($setting->min_redemption_points ?? 0);
                $redemptionValue = (float) ($setting->point_redemption_value ?? 0);

                if ($minRedemptionPoints > 0 && $redemptionValue > 0 && $availablePoints >= $minRedemptionPoints && $netSubtotal > 0) {
                    $maxPointsByAmount = (int) floor($netSubtotal / $redemptionValue);
                    $pointsToRedeem = min($availablePoints, $maxPointsByAmount, $desiredPoints > 0 ? $desiredPoints : $availablePoints);

                    if ($pointsToRedeem < $minRedemptionPoints) {
                        $pointsToRedeem = 0;
                    }

                    if ($pointsToRedeem > 0) {
                        $pointDiscountAmount = (int) app(\App\Services\PointService::class)->calculateRedemptionValue($pointsToRedeem);
                        $pointDiscountAmount = min($pointDiscountAmount, $netSubtotal);
                        $netSubtotal = max(0, (int) $netSubtotal - (int) $pointDiscountAmount);
                    }
                }
            }

            $taxBase = (bool) ($setting->discount_applies_before_tax ?? true) ? $netSubtotal : $grossSubtotal;
            $taxAmount = PriceService::calculateTax($taxBase, $taxRate);
            $totalBeforeRounding = $netSubtotal + $taxAmount;
            $roundingBase = max(0, (int) ($setting->rounding_base ?? 0));
            $roundingResult = PriceService::applyRounding($totalBeforeRounding, $roundingBase);
            $total = $roundingResult['total'];
            $roundingAmount = $roundingResult['rounding_amount'];

            $transaction = DB::transaction(function () use ($cartHash, $cartItems, $email, $grossSubtotal, $memberId, $name, $paymentIntentHash, $paymentSessionHash, $phone, $pointDiscountAmount, $pointsToRedeem, $roundingAmount, $table, $taxAmount, $taxRate, $total, $transactionCode, $voucher, $voucherDiscountAmount): Transaction {
                $transaction = new Transaction;
                $transaction->channel = 'self_order';
                $transaction->checkout_link = '-';
                $transaction->payment_method = 'cash';
                $transaction->member_id = is_numeric($memberId) ? (int) $memberId : null;
                $transaction->email = ! empty($email) ? (string) $email : null;
                $transaction->phone = $phone;
                $transaction->order_type = 'dine_in';
                $transaction->name = $name;
                $transaction->voucher_campaign_id = $voucher['voucher_campaign_id'];
                $transaction->voucher_code_id = $voucher['voucher_code_id'];
                $transaction->voucher_code = $voucher['voucher_code'];
                $transaction->subtotal = $grossSubtotal;
                $transaction->voucher_discount_amount = (int) $voucherDiscountAmount;
                $transaction->discount_total_amount = (int) $voucherDiscountAmount + (int) $pointDiscountAmount;
                $transaction->tax_percentage = $taxRate;
                $transaction->tax_amount = $taxAmount;
                $transaction->rounding_amount = $roundingAmount;
                $transaction->dining_table_id = $table->id;
                $transaction->total = $total;
                $transaction->external_id = (string) Str::uuid();
                $transaction->code = $transactionCode;
                $transaction->payment_status = 'pending';
                $transaction->self_order_token = (string) session('self_order_token');
                $transaction->payment_session_hash = $paymentSessionHash;
                $transaction->cart_hash = $cartHash;
                $transaction->payment_intent_hash = $paymentIntentHash;
                $transaction->save();

                if ($pointsToRedeem > 0) {
                    app(\App\Services\PointService::class)->redeemPoints($transaction, $pointsToRedeem);
                }

                $allocations = (array) ($voucher['allocations'] ?? []);

                $productIds = collect($cartItems)
                    ->map(fn (array $row) => (int) ($row['id'] ?? 0))
                    ->filter(fn (int $id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all();

                $productsById = $productIds === []
                    ? collect()
                    : Product::query()
                        ->whereIn('id', $productIds)
                        ->with(['packageItems.componentVariant.product'])
                        ->get()
                        ->keyBy('id');

                foreach ($cartItems as $index => $cartItem) {
                    if (! isset($cartItem['id'])) {
                        continue;
                    }
                    $price = (isset($cartItem['price_afterdiscount']) && (int) $cartItem['price_afterdiscount'] > 0 && (int) $cartItem['price_afterdiscount'] < (int) $cartItem['price']) ? (int) $cartItem['price_afterdiscount'] : (int) $cartItem['price'];
                    $parent = TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $cartItem['id'],
                        'product_variant_id' => $cartItem['variant_id'] ?? null,
                        'quantity' => $cartItem['quantity'],
                        'price' => $price,
                        'subtotal' => $price * $cartItem['quantity'],
                        'voucher_discount_amount' => (int) ($allocations[$index] ?? 0),
                        'note' => $cartItem['note'] ?? null,
                    ]);

                    $productId = (int) $cartItem['id'];
                    $product = $productsById->get($productId);
                    if (! $product || ! $product->is_package) {
                        continue;
                    }

                    $qty = (int) ($cartItem['quantity'] ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }

                    foreach ($product->packageItems as $packageItem) {
                        $componentVariant = $packageItem->componentVariant;
                        if (! $componentVariant) {
                            continue;
                        }

                        $componentQty = $qty * (int) $packageItem->quantity;
                        if ($componentQty <= 0) {
                            continue;
                        }

                        TransactionItem::create([
                            'transaction_id' => $transaction->id,
                            'parent_transaction_item_id' => (int) $parent->id,
                            'product_id' => (int) $componentVariant->product_id,
                            'product_variant_id' => (int) $componentVariant->id,
                            'quantity' => $componentQty,
                            'price' => 0,
                            'subtotal' => 0,
                            'voucher_discount_amount' => 0,
                            'note' => $cartItem['note'] ?? null,
                        ]);
                    }
                }

                return $transaction;
            });

            app(TransactionEventService::class)->record($transaction, 'self_order.created', [
                'payment_method' => (string) $transaction->payment_method,
                'payment_status' => (string) $transaction->payment_status,
                'total' => (int) $transaction->total,
            ]);

            event(new SelfOrderCashPendingCreated(
                (int) $transaction->id,
                (string) $transaction->code,
                (string) ($transaction->diningTable?->table_number ?? ''),
                (int) $transaction->total
            ));

            session([
                'has_unpaid_transaction' => false,
                'cart_items' => [],
                'payment_token' => null,
                'self_order_voucher_code' => null,
                'self_order_use_points' => false,
                'self_order_points_to_redeem' => 0,
                'external_id' => null,
            ]);

            return redirect()->route('self-order.payment.status', ['code' => $transactionCode]);

        } catch (\Exception $e) {
            Log::error('Failed to create cashier transaction', [
                'exception' => $e,
            ]);

            return view('livewire.self-order.payment.failure');
        }
    }

    public function processPayment(string $sessionToken)
    {
        $uuid = (string) Str::uuid();

        $cartItems = session('cart_items');

        if (! is_array($cartItems)) {
            $cartItems = [];
        }
        $checkout = app(SelfOrderCheckoutService::class);
        $result = $checkout->validateAndHydrateCartItems($cartItems);
        if ($result['changed']) {
            session(['cart_items' => $result['items']]);

            return redirect()->route('self-order.payment.cart')->with('error', 'Beberapa item tidak tersedia dan telah dihapus dari keranjang.');
        }
        $cartItems = $result['items'];
        $variants = $result['variants'];

        $name = session('name');
        $phone = session('phone');
        $email = session('email');
        $memberId = session('member_id');
        $tableId = session('dining_table_id');

        if (empty($cartItems) || empty($name) || empty($phone) || empty($tableId)) {
            return redirect()
                ->route('self-order.payment.cart')
                ->with('error', 'Data pemesanan belum lengkap.');
        }

        $table = DiningTable::find($tableId);
        if (! $table) {
            return redirect()
                ->route('self-order.invalid')
                ->with('error', 'Meja tidak valid.');
        }

        $transactionCode = Transaction::generateUniqueCode();

        try {
            $checkout->assertSufficientIngredientStock($cartItems, $variants);

            $cartHash = $checkout->cartHash($cartItems);
            $paymentSessionHash = hash('sha256', (string) session('self_order_token').'|'.$sessionToken);
            $paymentIntentHash = hash('sha256', (string) session('self_order_token').'|'.$paymentSessionHash.'|'.$cartHash.'|online');

            $existing = Transaction::query()
                ->where('payment_intent_hash', $paymentIntentHash)
                ->first();
            if ($existing) {
                session(['external_id' => $existing->external_id]);
                session(['has_unpaid_transaction' => $existing->payment_status === 'pending']);

                if (! empty($existing->checkout_link) && $existing->checkout_link !== '-') {
                    return redirect($existing->checkout_link);
                }

                return redirect()->route('self-order.payment.status', ['code' => $existing->code]);
            }

            $grossSubtotal = 0;
            foreach ($cartItems as $item) {
                $price = (isset($item['price_afterdiscount']) && (int) $item['price_afterdiscount'] > 0 && (int) $item['price_afterdiscount'] < (int) $item['price']) ? (int) $item['price_afterdiscount'] : (int) $item['price'];
                $qty = (int) ($item['quantity'] ?? 1);
                $grossSubtotal += $price * $qty;
            }

            $voucher = $this->resolveVoucherForCart($cartItems, $memberId, (string) $phone);
            if (! $voucher['ok']) {
                return redirect()
                    ->route('self-order.payment.page')
                    ->with('error', (string) $voucher['message']);
            }

            $voucherDiscountAmount = (int) $voucher['voucher_discount_amount'];
            $netSubtotal = max(0, (int) $grossSubtotal - $voucherDiscountAmount);

            $setting = Setting::current();

            $pointsToRedeem = 0;
            $pointDiscountAmount = 0;

            if ((bool) session('self_order_use_points') && (string) session('customer_type') === 'member' && is_numeric($memberId)) {
                $member = Member::query()->find((int) $memberId);
                $availablePoints = (int) ($member?->points ?? 0);
                $desiredPoints = max(0, (int) session('self_order_points_to_redeem', 0));

                $minRedemptionPoints = (int) ($setting->min_redemption_points ?? 0);
                $redemptionValue = (float) ($setting->point_redemption_value ?? 0);

                if ($minRedemptionPoints > 0 && $redemptionValue > 0 && $availablePoints >= $minRedemptionPoints && $netSubtotal > 0) {
                    $maxPointsByAmount = (int) floor($netSubtotal / $redemptionValue);
                    $pointsToRedeem = min($availablePoints, $maxPointsByAmount, $desiredPoints > 0 ? $desiredPoints : $availablePoints);

                    if ($pointsToRedeem < $minRedemptionPoints) {
                        $pointsToRedeem = 0;
                    }

                    if ($pointsToRedeem > 0) {
                        $pointDiscountAmount = (int) app(\App\Services\PointService::class)->calculateRedemptionValue($pointsToRedeem);
                        $pointDiscountAmount = min($pointDiscountAmount, $netSubtotal);
                        $netSubtotal = max(0, (int) $netSubtotal - (int) $pointDiscountAmount);
                    }
                }
            }

            $allocations = (array) ($voucher['allocations'] ?? []);
            $itemDetails = collect($cartItems)
                ->map(function ($item, $index) use ($allocations) {
                    $price = (isset($item['price_afterdiscount']) && (int) $item['price_afterdiscount'] > 0 && (int) $item['price_afterdiscount'] < (int) $item['price']) ? (int) $item['price_afterdiscount'] : (int) $item['price'];
                    $qty = (int) ($item['quantity'] ?? 1);
                    $subtotal = $price * $qty;
                    $voucherAlloc = (int) ($allocations[$index] ?? 0);
                    $netLineSubtotal = max(0, $subtotal - $voucherAlloc);
                    $name = (string) ($item['name'] ?? 'Item');

                    return [
                        'id' => 'item',
                        'quantity' => 1,
                        'price' => $netLineSubtotal,
                        'name' => $name.($qty > 1 ? ' x'.$qty : ''),
                    ];
                })
                ->values()
                ->toArray();

            $taxRate = (float) ($setting->tax_rate ?? 0);
            $taxBase = (bool) ($setting->discount_applies_before_tax ?? true) ? $netSubtotal : $grossSubtotal;
            $taxAmount = PriceService::calculateTax($taxBase, $taxRate);
            $totalBeforeRounding = $netSubtotal + $taxAmount;
            $roundingAmount = 0;
            $feeAmount = (int) round($totalBeforeRounding * 0.007);
            $totalAfterFee = $totalBeforeRounding + $feeAmount;

            // Add tax item to Midtrans details if tax > 0
            if ($taxAmount > 0) {
                $itemDetails[] = [
                    'id' => 'tax',
                    'quantity' => 1,
                    'price' => $taxAmount,
                    'name' => 'PB1 ('.$taxRate.'%)',
                ];
            }

            if ($pointDiscountAmount > 0) {
                $itemDetails[] = [
                    'id' => 'point_discount',
                    'quantity' => 1,
                    'price' => -1 * (int) $pointDiscountAmount,
                    'name' => 'Diskon Poin',
                ];
            }

            $itemDetails[] = [
                'id' => 'fee',
                'quantity' => 1,
                'price' => $feeAmount,
                'name' => 'Biaya Admin (0,7%)',
            ];

            $customerDetails = [
                'first_name' => $name,
                'phone' => $phone,
            ];
            if (! empty($email)) {
                $customerDetails['email'] = (string) $email;
            }

            $params = [
                'transaction_details' => [
                    'order_id' => $uuid,
                    'gross_amount' => $totalAfterFee,
                ],
                'item_details' => $itemDetails,
                'customer_details' => $customerDetails,
                'custom_field1' => $transactionCode,
                'enabled_payments' => [
                    'other_qris',
                ],
                'callbacks' => [
                    'finish' => route('self-order.payment.status', ['code' => $transactionCode]),
                ],
            ];
            $invoice = MidtransSnap::createTransaction($params);

            $transaction = DB::transaction(function () use ($cartHash, $cartItems, $email, $feeAmount, $grossSubtotal, $invoice, $memberId, $name, $paymentIntentHash, $paymentSessionHash, $phone, $pointDiscountAmount, $pointsToRedeem, $roundingAmount, $table, $taxAmount, $taxRate, $totalAfterFee, $transactionCode, $uuid, $voucher, $voucherDiscountAmount): Transaction {
                $transaction = new Transaction;
                $transaction->channel = 'self_order';
                $transaction->checkout_link = $invoice->redirect_url ?? '-';
                $transaction->payment_method = 'qris_midtrans';
                $transaction->member_id = is_numeric($memberId) ? (int) $memberId : null;
                $transaction->email = ! empty($email) ? (string) $email : null;
                $transaction->phone = $phone;
                $transaction->order_type = 'dine_in';
                $transaction->name = $name;
                $transaction->voucher_campaign_id = $voucher['voucher_campaign_id'];
                $transaction->voucher_code_id = $voucher['voucher_code_id'];
                $transaction->voucher_code = $voucher['voucher_code'];
                $transaction->subtotal = $grossSubtotal;
                $transaction->voucher_discount_amount = (int) $voucherDiscountAmount;
                $transaction->discount_total_amount = (int) $voucherDiscountAmount + (int) $pointDiscountAmount;
                $transaction->tax_percentage = $taxRate;
                $transaction->tax_amount = $taxAmount;
                $transaction->payment_fee_amount = $feeAmount;
                $transaction->rounding_amount = $roundingAmount;
                $transaction->dining_table_id = $table->id;
                $transaction->total = $totalAfterFee;
                $transaction->external_id = $uuid;
                $transaction->code = $transactionCode;
                $transaction->payment_status = 'pending';
                $transaction->self_order_token = (string) session('self_order_token');
                $transaction->payment_session_hash = $paymentSessionHash;
                $transaction->cart_hash = $cartHash;
                $transaction->payment_intent_hash = $paymentIntentHash;
                $transaction->midtrans_snap_token = property_exists($invoice, 'token') ? (string) ($invoice->token ?? '') : null;
                $transaction->midtrans_redirect_url = property_exists($invoice, 'redirect_url') ? (string) ($invoice->redirect_url ?? '') : null;
                $transaction->save();

                if ($pointsToRedeem > 0) {
                    app(\App\Services\PointService::class)->redeemPoints($transaction, $pointsToRedeem);
                }

                $allocations = (array) ($voucher['allocations'] ?? []);

                $productIds = collect($cartItems)
                    ->map(fn (array $row) => (int) ($row['id'] ?? 0))
                    ->filter(fn (int $id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all();

                $productsById = $productIds === []
                    ? collect()
                    : Product::query()
                        ->whereIn('id', $productIds)
                        ->with(['packageItems.componentVariant.product'])
                        ->get()
                        ->keyBy('id');

                foreach ($cartItems as $index => $cartItem) {
                    $price = (isset($cartItem['price_afterdiscount']) && (int) $cartItem['price_afterdiscount'] > 0 && (int) $cartItem['price_afterdiscount'] < (int) $cartItem['price']) ? (int) $cartItem['price_afterdiscount'] : (int) $cartItem['price'];

                    $parent = TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $cartItem['id'],
                        'product_variant_id' => $cartItem['variant_id'] ?? null,
                        'quantity' => $cartItem['quantity'],
                        'price' => $price,
                        'subtotal' => $price * $cartItem['quantity'],
                        'voucher_discount_amount' => (int) ($allocations[$index] ?? 0),
                        'note' => $cartItem['note'] ?? null,
                    ]);

                    $productId = (int) ($cartItem['id'] ?? 0);
                    $product = $productsById->get($productId);
                    if (! $product || ! $product->is_package) {
                        continue;
                    }

                    $qty = (int) ($cartItem['quantity'] ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }

                    foreach ($product->packageItems as $packageItem) {
                        $componentVariant = $packageItem->componentVariant;
                        if (! $componentVariant) {
                            continue;
                        }

                        $componentQty = $qty * (int) $packageItem->quantity;
                        if ($componentQty <= 0) {
                            continue;
                        }

                        TransactionItem::create([
                            'transaction_id' => $transaction->id,
                            'parent_transaction_item_id' => (int) $parent->id,
                            'product_id' => (int) $componentVariant->product_id,
                            'product_variant_id' => (int) $componentVariant->id,
                            'quantity' => $componentQty,
                            'price' => 0,
                            'subtotal' => 0,
                            'voucher_discount_amount' => 0,
                            'note' => $cartItem['note'] ?? null,
                        ]);
                    }
                }

                return $transaction;
            });

            app(TransactionEventService::class)->record($transaction, 'self_order.created', [
                'payment_method' => (string) $transaction->payment_method,
                'payment_status' => (string) $transaction->payment_status,
                'total' => (int) $transaction->total,
                'midtrans_snap_token' => $transaction->midtrans_snap_token,
            ]);

            session(['external_id' => $uuid]);
            session(['has_unpaid_transaction' => true]);

            return redirect($transaction->checkout_link);

        } catch (\Exception $e) {
            Log::error('Failed to create transaction', [
                'exception' => $e,
            ]);

            return redirect()
                ->route('self-order.payment.failure')
                ->with('error', 'Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.');
        }
    }

    public function handleWebhook(Request $request)
    {
        $orderId = (string) $request->input('order_id');
        $statusCode = (string) $request->input('status_code');
        $grossAmount = (string) $request->input('gross_amount');
        $signatureKey = (string) $request->input('signature_key');

        $serverKey = (string) config('midtrans.server_key');
        $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signatureKey === '') {
            return response()->json([
                'message' => 'Invalid webhook request.',
            ], 400);
        }

        if (! hash_equals($expectedSignature, $signatureKey)) {
            return response()->json([
                'message' => 'Unauthorized webhook request.',
            ], 401);
        }

        try {
            $midStatus = (string) $request->input('transaction_status');

            $transaction = Transaction::where('external_id', $orderId)->first();
            if (! $transaction) {
                Log::warning('Webhook received for unknown transaction', [
                    'order_id' => $orderId,
                    'transaction_status' => $midStatus,
                ]);

                return response()->json([
                    'code' => 200,
                    'message' => 'Webhook ignored',
                ]);
            }

            $previous = (string) $transaction->payment_status;
            $mapped = $this->mapMidtransStatusToPaymentStatus($midStatus);
            $transaction->payment_status = $mapped;
            $transaction->midtrans_status = $midStatus !== '' ? $midStatus : null;
            $transaction->midtrans_payload = $request->all();
            if ($mapped === 'paid' && ! $transaction->paid_at) {
                $transaction->paid_at = now();
            }

            $transaction->save();

            if ($previous !== (string) $transaction->payment_status) {
                app(TransactionEventService::class)->record($transaction, 'payment.status_updated', [
                    'source' => 'webhook',
                    'previous' => $previous,
                    'current' => (string) $transaction->payment_status,
                    'midtrans_status' => (string) ($transaction->midtrans_status ?? ''),
                ]);
            }

            if ($mapped === 'paid' && $previous !== 'paid') {
                $this->finalizeVoucherRedemption($transaction);
                $transaction->loadMissing('diningTable');
                event(new NewMidtransTransaction(
                    $transaction->id,
                    (string) $transaction->code,
                    (string) ($transaction->diningTable?->table_number ?? '')
                ));
                app(ReceiptEmailService::class)->queueIfNeeded($transaction);
            }

            if ((string) $transaction->channel === 'self_order' && (string) ($transaction->self_order_token ?? '') !== '' && $previous !== (string) $transaction->payment_status) {
                event(new SelfOrderPaymentUpdated($transaction));
            }

            return response()->json([
                'code' => 200,
                'message' => 'Webhook received',
                'status' => $mapped,
                'payment_method' => (string) $transaction->payment_method,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to handle webhook', [
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'Failed to handle webhook.',
            ], 500);
        }

    }

    public function clearSession()
    {
        Session::forget(['external_id', 'has_unpaid_transaction', 'cart_items', 'payment_token', 'self_order_voucher_code', 'self_order_use_points', 'self_order_points_to_redeem']);
        Session::save();
    }

    public function receipt($code)
    {
        $transaction = Transaction::with('transactionItems.product', 'transactionItems.variant', 'diningTable')->where('code', $code)->firstOrFail();

        $token = trim((string) request()->query('token', ''));
        $sessionToken = trim((string) session('self_order_token', ''));
        $sessionExternalId = trim((string) session('external_id', ''));
        $trxToken = trim((string) ($transaction->self_order_token ?? ''));

        if ($trxToken !== '' && ($token === $trxToken || $sessionToken === $trxToken)) {
            return view('livewire.self-order.payment.receipt', [
                'transaction' => $transaction,
            ]);
        }

        if ($sessionExternalId !== '' && hash_equals($sessionExternalId, (string) $transaction->external_id)) {
            return view('livewire.self-order.payment.receipt', [
                'transaction' => $transaction,
            ]);
        }

        $user = auth()->user();
        if ($user && $user->can('transactions.details')) {
            return view('livewire.self-order.payment.receipt', [
                'transaction' => $transaction,
            ]);
        }

        abort(403);
    }

    private function resolveVoucherForCart(array $cartItems, $memberId, string $phone): array
    {
        $code = session('self_order_voucher_code');
        if (! is_string($code) || trim($code) === '') {
            return [
                'ok' => true,
                'message' => '',
                'voucher_campaign_id' => null,
                'voucher_code_id' => null,
                'voucher_code' => null,
                'voucher_discount_amount' => 0,
                'allocations' => [],
            ];
        }

        $member = null;
        if (is_numeric($memberId)) {
            $member = Member::query()->find((int) $memberId);
        }

        $guestId = $member ? null : (trim($phone) !== '' ? $phone : null);

        $elig = app(\App\Services\Vouchers\VoucherEligibilityService::class)
            ->validate($code, $member, $cartItems, $guestId);

        if (! (bool) ($elig['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => (string) ($elig['message'] ?? 'Voucher tidak bisa digunakan.'),
                'voucher_campaign_id' => null,
                'voucher_code_id' => null,
                'voucher_code' => null,
                'voucher_discount_amount' => 0,
                'allocations' => [],
            ];
        }

        $voucherCode = $elig['voucher_code'] ?? null;
        if (! ($voucherCode instanceof VoucherCode)) {
            return [
                'ok' => false,
                'message' => 'Voucher tidak bisa digunakan.',
                'voucher_campaign_id' => null,
                'voucher_code_id' => null,
                'voucher_code' => null,
                'voucher_discount_amount' => 0,
                'allocations' => [],
            ];
        }

        $allocations = [];
        foreach ($cartItems as $index => $item) {
            $allocations[(int) $index] = 0;
        }

        $eligibleLines = (array) ($elig['eligible_lines'] ?? []);
        $lineAllocs = (array) ($elig['allocations'] ?? []);
        foreach ($eligibleLines as $i => $line) {
            $idx = (int) ($line['index'] ?? -1);
            if ($idx >= 0) {
                $allocations[$idx] = (int) ($lineAllocs[$i] ?? 0);
            }
        }

        return [
            'ok' => true,
            'message' => (string) ($elig['message'] ?? 'Voucher dapat digunakan.'),
            'voucher_campaign_id' => (int) $voucherCode->voucher_campaign_id,
            'voucher_code_id' => (int) $voucherCode->id,
            'voucher_code' => (string) $voucherCode->code,
            'voucher_discount_amount' => (int) ($elig['discount_amount'] ?? 0),
            'allocations' => $allocations,
        ];
    }

    private function finalizeVoucherRedemption(Transaction $transaction): void
    {
        if (! $transaction->voucher_code_id || (int) $transaction->voucher_discount_amount <= 0) {
            return;
        }

        $exists = VoucherRedemption::query()
            ->where('transaction_id', (int) $transaction->id)
            ->exists();

        if ($exists) {
            return;
        }

        DB::transaction(function () use ($transaction) {
            $trx = Transaction::query()->whereKey((int) $transaction->id)->lockForUpdate()->first();
            if (! $trx || ! $trx->voucher_code_id || (int) $trx->voucher_discount_amount <= 0) {
                return;
            }

            $code = VoucherCode::query()->whereKey((int) $trx->voucher_code_id)->with('campaign')->lockForUpdate()->first();
            if (! $code || ! $code->campaign) {
                return;
            }

            VoucherRedemption::query()->create([
                'voucher_campaign_id' => (int) $trx->voucher_campaign_id,
                'voucher_code_id' => (int) $trx->voucher_code_id,
                'transaction_id' => (int) $trx->id,
                'member_id' => $trx->member_id ? (int) $trx->member_id : null,
                'guest_identifier' => $trx->member_id ? null : ($trx->phone ? (string) $trx->phone : null),
                'discount_amount' => (int) $trx->voucher_discount_amount,
                'snapshot' => [
                    'campaign' => [
                        'id' => (int) $code->campaign->id,
                        'name' => (string) $code->campaign->name,
                        'discount_type' => (string) $code->campaign->discount_type,
                        'discount_value' => (int) $code->campaign->discount_value,
                        'max_discount_amount' => $code->campaign->max_discount_amount === null ? null : (int) $code->campaign->max_discount_amount,
                        'min_eligible_subtotal' => $code->campaign->min_eligible_subtotal === null ? null : (int) $code->campaign->min_eligible_subtotal,
                        'is_member_only' => (bool) $code->campaign->is_member_only,
                    ],
                ],
                'redeemed_at' => $trx->paid_at ?? now(),
            ]);

            $code->increment('times_redeemed');

            app(TransactionEventService::class)->record($trx, 'voucher.redeemed', [
                'voucher_campaign_id' => (int) $trx->voucher_campaign_id,
                'voucher_code_id' => (int) $trx->voucher_code_id,
                'voucher_code' => (string) $trx->voucher_code,
                'discount_amount' => (int) $trx->voucher_discount_amount,
            ]);
        });
    }

    private function mapMidtransStatusToPaymentStatus(?string $status): string
    {
        if (in_array($status, ['settlement', 'capture'], true)) {
            return 'paid';
        }

        if ($status === 'pending') {
            return 'pending';
        }

        if ($status === 'expire') {
            return 'expired';
        }

        if (in_array($status, ['cancel', 'deny', 'failure'], true)) {
            return 'failed';
        }

        return 'pending';
    }
}
