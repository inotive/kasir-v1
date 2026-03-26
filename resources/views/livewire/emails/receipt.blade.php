<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    @php
        $setting = \App\Models\Setting::current();
        $publicStorageUrl = rtrim((string) config('filesystems.disks.public.url'), '/');
        $logoPath = trim((string) ($setting->store_logo ?? ''), '/');
        $logoUrl = $logoPath !== '' && $publicStorageUrl !== '' ? $publicStorageUrl.'/'.$logoPath : null;
        $logoHost = $logoUrl ? (string) (parse_url($logoUrl, PHP_URL_HOST) ?? '') : '';
        if (in_array($logoHost, ['127.0.0.1', 'localhost'], true)) {
            $logoUrl = null;
        }
        $storeName = (string) ($setting->store_name ?? config('app.name'));
        $storePhone = trim((string) ($setting->phone ?? ''));
        $storeAddress = trim((string) ($setting->address ?? ''));
        $brand = '#dc2626';
        $brandDark = '#b91c1c';
        $surface = '#ffffff';
        $background = '#f3f4f6';
        $ink = '#111827';
        $muted = '#64748b';
        $mutedLight = '#94a3b8';
        $paidAt = $transaction->paid_at ?? $transaction->updated_at;
        $detailUrl = route('self-order.payment.receipt', ['code' => $transaction->code, 'token' => $transaction->self_order_token]);
        $orderTypeLabel = \App\Helpers\DataLabelHelper::enum($transaction->order_type ?? null, 'order_type');
        $paymentMethodLabel = \App\Helpers\DataLabelHelper::enum($transaction->payment_method ?? null, 'payment_method');
        $subtotal = (int) ($transaction->subtotal ?? 0);
        $taxAmount = (int) ($transaction->tax_amount ?? 0);
        $taxPercentage = (string) ($transaction->tax_percentage ?? '');
        $roundingAmount = (int) ($transaction->rounding_amount ?? 0);
        $paymentFee = (int) ($transaction->payment_fee_amount ?? 0);
        $voucherDiscount = (int) ($transaction->voucher_discount_amount ?? 0);
        $voucherCode = trim((string) ($transaction->voucher_code ?? ''));
        $pointDiscount = (int) ($transaction->point_discount_amount ?? 0);
        $pointsRedeemed = (int) ($transaction->points_redeemed ?? 0);
        $manualDiscount = (int) ($transaction->manual_discount_amount ?? 0);
        $total = (int) ($transaction->total ?? 0);
        $cashReceived = (int) ($transaction->cash_received ?? 0);
        $cashChange = (int) ($transaction->cash_change ?? 0);
        $tableNumber = $transaction->diningTable?->table_number ? (string) $transaction->diningTable->table_number : '';
    @endphp

    <div style="display:none!important; visibility:hidden; opacity:0; color:transparent; height:0; width:0; overflow:hidden; mso-hide:all;">
        Struk pembayaran #{{ $transaction->code }} dari {{ $storeName }}. Total Rp {{ number_format($total, 0, ',', '.') }}.
    </div>
    
    <div style="width: 100%; background-color: {{ $background }}; padding: 32px 0;">
        <div style="max-width: 640px; margin: 0 auto; background-color: {{ $surface }}; border-radius: 14px; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08); overflow: hidden;">
            
            <div style="background-color: {{ $brand }}; padding: 28px 28px 20px 28px; text-align: center;">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $storeName }}" style="max-height: 56px; margin-bottom: 14px;">
                @else
                    <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 800; letter-spacing: 0.2px;">{{ $storeName }}</h1>
                @endif
                @if($storeAddress !== '')
                    <div style="color: rgba(255, 255, 255, 0.92); font-size: 13px; margin-top: 6px; line-height: 1.4;">{{ $storeAddress }}</div>
                @endif
                @if($storePhone !== '')
                    <div style="color: rgba(255, 255, 255, 0.92); font-size: 13px; margin-top: 4px;">{{ $storePhone }}</div>
                @endif
            </div>

            <div style="background-color: {{ $brandDark }}; color: #ffffff; padding: 14px 28px; text-align: center; font-weight: 800; font-size: 14px; letter-spacing: 0.3px;">
                PEMBAYARAN BERHASIL
            </div>

            <div style="padding: 26px 28px 10px 28px;">
                <div style="margin-bottom: 18px;">
                    <div style="font-size: 14px; color: #111827; font-weight: 800; margin-bottom: 6px;">Ringkasan Transaksi</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 0; color: {{ $muted }}; font-size: 13px;">Kode Transaksi</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: 800; color: {{ $ink }}; font-size: 13px;">#{{ $transaction->code }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: {{ $muted }}; font-size: 13px;">Tanggal</td>
                            <td style="padding: 8px 0; text-align: right; color: {{ $ink }}; font-size: 13px;">{{ $paidAt?->format('d M Y, H:i') }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: {{ $muted }}; font-size: 13px;">Metode Pembayaran</td>
                            <td style="padding: 8px 0; text-align: right; color: {{ $ink }}; font-size: 13px;">{{ $paymentMethodLabel }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: {{ $muted }}; font-size: 13px;">Tipe Pesanan</td>
                            <td style="padding: 8px 0; text-align: right; color: {{ $ink }}; font-size: 13px;">{{ $orderTypeLabel }}</td>
                        </tr>
                        @if($tableNumber !== '')
                        <tr>
                            <td style="padding: 8px 0; color: {{ $muted }}; font-size: 13px;">Nomor Meja</td>
                            <td style="padding: 8px 0; text-align: right; color: {{ $ink }}; font-size: 13px;">{{ $tableNumber }}</td>
                        </tr>
                        @endif
                    </table>
                </div>

                <div style="border-top: 1px solid #e5e7eb; margin: 18px 0;"></div>

                <div style="margin-bottom: 18px;">
                    <div style="font-size: 14px; color: #111827; font-weight: 800; margin-bottom: 6px;">Informasi Pelanggan</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 6px 0; color: #64748b; font-size: 13px; width: 120px;">Nama</td>
                            <td style="padding: 6px 0; color: #111827; font-size: 13px;">{{ $transaction->name }}</td>
                        </tr>
                        @if($transaction->phone)
                        <tr>
                            <td style="padding: 6px 0; color: #64748b; font-size: 13px;">No. HP</td>
                            <td style="padding: 6px 0; color: #111827; font-size: 13px;">{{ $transaction->phone }}</td>
                        </tr>
                        @endif
                        @if(($transaction->email ?? '') !== '')
                        <tr>
                            <td style="padding: 6px 0; color: #64748b; font-size: 13px;">Email</td>
                            <td style="padding: 6px 0; color: #111827; font-size: 13px;">{{ $transaction->email }}</td>
                        </tr>
                        @endif
                    </table>
                </div>

                <div style="border-top: 1px solid #e5e7eb; margin: 18px 0;"></div>

                <div style="margin-bottom: 18px;">
                    <div style="font-size: 14px; color: #111827; font-weight: 800; margin-bottom: 10px;">Rincian Pesanan</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background-color: #f8fafc;">
                            <tr>
                                <th style="padding: 10px; text-align: left; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; border-bottom: 1px solid #e5e7eb;">Item</th>
                                <th style="padding: 10px; text-align: right; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; border-bottom: 1px solid #e5e7eb;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transaction->transactionItems as $item)
                            <tr>
                                <td style="padding: 12px 10px; border-bottom: 1px solid #f1f5f9;">
                                    <div style="font-weight: 800; color: #111827; font-size: 13px; line-height: 1.35;">{{ $item->product->name ?? 'Item' }}</div>
                                    @php
                                        $variantDisplay = \App\Support\Products\ItemNameFormatter::displayVariantName((int) $item->product_id, $item->variant?->name);
                                    @endphp
                                    @if($variantDisplay !== '')
                                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Varian: {{ $variantDisplay }}</div>
                                    @endif
                                    @if(! empty($item->note))
                                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Catatan: {{ $item->note }}</div>
                                    @endif
                                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">{{ (int) $item->quantity }} x Rp {{ number_format((int) $item->price, 0, ',', '.') }}</div>
                                </td>
                                <td style="padding: 12px 10px; text-align: right; vertical-align: top; border-bottom: 1px solid #f1f5f9; color: #111827; font-size: 13px; font-weight: 800;">
                                    Rp {{ number_format((int) $item->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="border-top: 1px solid #e5e7eb; margin: 18px 0;"></div>

                <div style="background-color: #f8fafc; border-radius: 12px; padding: 18px;">
                    <div style="font-size: 14px; color: #111827; font-weight: 800; margin-bottom: 8px;">Rincian Pembayaran</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 7px 0; color: #64748b; font-size: 13px;">Subtotal</td>
                            <td style="padding: 7px 0; text-align: right; color: #111827; font-size: 13px;">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @if($voucherDiscount > 0)
                        <tr>
                            <td style="padding: 7px 0; color: #64748b; font-size: 13px;">Diskon Voucher{{ $voucherCode !== '' ? ' ('.$voucherCode.')' : '' }}</td>
                            <td style="padding: 7px 0; text-align: right; color: #111827; font-size: 13px;">-Rp {{ number_format($voucherDiscount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        @if($pointDiscount > 0)
                        <tr>
                            <td style="padding: 7px 0; color: #64748b; font-size: 13px;">Diskon Poin{{ $pointsRedeemed > 0 ? ' ('.number_format($pointsRedeemed, 0, ',', '.').' poin)' : '' }}</td>
                            <td style="padding: 7px 0; text-align: right; color: #111827; font-size: 13px;">-Rp {{ number_format($pointDiscount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        @if($manualDiscount > 0)
                        <tr>
                            <td style="padding: 7px 0; color: #64748b; font-size: 13px;">Diskon</td>
                            <td style="padding: 7px 0; text-align: right; color: #111827; font-size: 13px;">-Rp {{ number_format($manualDiscount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        @if($transaction->tax_amount > 0)
                        <tr>
                            <td style="padding: 7px 0; color: #64748b; font-size: 13px;">Pajak PB1{{ $taxPercentage !== '' ? ' ('.$taxPercentage.'%)' : '' }}</td>
                            <td style="padding: 7px 0; text-align: right; color: #111827; font-size: 13px;">Rp {{ number_format($taxAmount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        @if($paymentFee > 0)
                        <tr>
                            <td style="padding: 7px 0; color: #64748b; font-size: 13px;">Biaya Admin</td>
                            <td style="padding: 7px 0; text-align: right; color: #111827; font-size: 13px;">Rp {{ number_format($paymentFee, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        @if($roundingAmount !== 0)
                        <tr>
                            <td style="padding: 7px 0; color: #64748b; font-size: 13px;">Pembulatan</td>
                            <td style="padding: 7px 0; text-align: right; color: #111827; font-size: 13px;">Rp {{ number_format($roundingAmount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td style="padding-top: 12px; border-top: 1px dashed #cbd5e1; font-weight: 800; color: #111827; font-size: 15px;">Total Pembayaran</td>
                            <td style="padding-top: 12px; border-top: 1px dashed #cbd5e1; text-align: right; font-weight: 900; color: #dc2626; font-size: 15px;">Rp {{ number_format($total, 0, ',', '.') }}</td>
                        </tr>
                        @if(($transaction->payment_method ?? '') === 'cash' && $cashReceived > 0)
                        <tr>
                            <td style="padding-top: 10px; color: #64748b; font-size: 13px;">Tunai Diterima</td>
                            <td style="padding-top: 10px; text-align: right; color: #111827; font-size: 13px;">Rp {{ number_format($cashReceived, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 7px 0 0 0; color: #64748b; font-size: 13px;">Kembalian</td>
                            <td style="padding: 7px 0 0 0; text-align: right; color: #111827; font-size: 13px;">Rp {{ number_format($cashChange, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>

                <div style="padding: 16px 0 6px 0; text-align: center;">
                    <a href="{{ $detailUrl }}" style="display: inline-block; background-color: {{ $brand }}; color: #ffffff; text-decoration: none; padding: 12px 18px; border-radius: 12px; font-weight: 800; font-size: 13px; letter-spacing: 0.2px;">Lihat Struk Digital</a>
                    <div style="margin-top: 10px; font-size: 12px; color: {{ $muted }}; line-height: 1.45;">Jika tombol tidak bisa diklik, salin tautan ini ke browser:<br><span style="color: {{ $ink }};">{{ $detailUrl }}</span></div>
                </div>
            </div>

            <div style="background-color: #f8fafc; padding: 18px 28px; text-align: center; border-top: 1px solid #e5e7eb;">
                <p style="margin: 0; font-size: 14px; color: #111827; font-weight: 900;">Terima kasih telah memesan di {{ $storeName }}.</p>
                <p style="margin: 6px 0 0 0; font-size: 12px; color: #64748b; line-height: 1.45;">Simpan email ini sebagai bukti pembayaran yang sah. Untuk pertanyaan atau koreksi pesanan, silakan hubungi kami.</p>
                
                <div style="margin-top: 14px; font-size: 12px; color: {{ $mutedLight }};">
                    &copy; {{ date('Y') }} {{ $storeName }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
