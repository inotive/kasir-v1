<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .muted { color: #6b7280; }
        .summary-table { width: 50%; border-collapse: collapse; margin: 12px 0; }
        .summary-table td { padding: 3px 6px; }
        .summary-table td.label { font-weight: bold; width: 160px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.data thead { display: table-header-group; }
        table.data th, table.data td { border: 1px solid #d1d5db; padding: 4px 6px; font-size: 10px; }
        table.data th { background-color: #f3f4f6; text-align: left; }
        table.data td.text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $meta['storeName'] ?? config('app.name') }}</h1>
    <p class="muted">{{ $meta['reportTitle'] ?? 'Laporan Transaksi' }}</p>
    <p class="muted">Periode: {{ $meta['periodLabel'] ?? '' }} &middot; Kasir/Sumber: {{ $meta['cashierLabel'] ?? 'Semua Kasir/Sumber' }} &middot; Dibuat: {{ $meta['generatedAt'] ?? '' }}</p>

    <table class="summary-table">
        <tr><td class="label">Total Transaksi</td><td>{{ number_format((int) ($summary['txCount'] ?? 0), 0, ',', '.') }}</td></tr>
        <tr><td class="label">Omzet (Net Sales)</td><td>Rp{{ number_format((float) ($summary['netRevenue'] ?? 0), 0, ',', '.') }}</td></tr>
        <tr><td class="label">Item Terjual</td><td>{{ number_format((int) ($summary['itemsSold'] ?? 0), 0, ',', '.') }}</td></tr>
        <tr><td class="label">Rata-rata Order</td><td>Rp{{ number_format((float) ($summary['avgOrder'] ?? 0), 0, ',', '.') }}</td></tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Telepon</th>
                <th>Kasir/Sumber</th>
                <th>Metode Bayar</th>
                <th>Tipe Order</th>
                <th>Status</th>
                <th>Produk</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['code'] ?? '' }}</td>
                    <td>{{ $row['created_at'] ?? '' }}</td>
                    <td>{{ $row['customer_name'] ?? '' }}</td>
                    <td>{{ $row['phone'] ?? '' }}</td>
                    <td>{{ $row['cashier_name'] ?? '' }}</td>
                    <td>{{ \App\Helpers\DataLabelHelper::enum($row['payment_method'] ?? null, 'payment_method') }}</td>
                    <td>{{ \App\Helpers\DataLabelHelper::enum($row['order_type'] ?? null, 'order_type') }}</td>
                    <td>{{ \App\Helpers\DataLabelHelper::enum($row['payment_status'] ?? null, 'payment_status') }}</td>
                    <td>{{ $row['products'] ?? '' }}</td>
                    <td class="text-right">Rp{{ number_format((float) ($row['total'] ?? 0), 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="10">Tidak ada transaksi pada rentang ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
