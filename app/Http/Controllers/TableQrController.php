<?php

namespace App\Http\Controllers;

use App\Models\DiningTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TableQrController extends Controller
{
    public function __invoke(Request $request, string $code): RedirectResponse
    {
        $id = \decode_qr_code($code);
        if (! $id) {
            return redirect()->route('self-order.invalid');
        }

        $table = DiningTable::query()->find((int) $id);
        if (! $table) {
            return redirect()->route('self-order.invalid');
        }

        if (auth()->check()) {
            return redirect()->route('pos.index', [
                'type' => 'dine_in',
                'table' => $table->id,
            ]);
        }

        session(['dining_table_id' => (int) $table->id]);
        session([
            'self_order_token' => Str::random(40),
            'has_unpaid_transaction' => false,
            'cart_items' => [],
            'payment_token' => null,
            'external_id' => null,
        ]);
        session()->forget([
            'customer_ready',
            'customer_type',
            'member_id',
            'name',
            'phone',
            'email',
        ]);

        return redirect()->route('self-order.start');
    }
}
