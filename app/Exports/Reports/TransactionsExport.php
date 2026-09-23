<?php

namespace App\Exports\Reports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TransactionsExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    public function __construct(
        private readonly array $meta,
        private readonly array $summary,
        private readonly array $rows,
    ) {}

    public function array(): array
    {
        $storeName = (string) ($this->meta['storeName'] ?? config('app.name'));
        $reportTitle = (string) ($this->meta['reportTitle'] ?? 'Laporan Transaksi');
        $periodLabel = (string) ($this->meta['periodLabel'] ?? '');
        $cashierLabel = (string) ($this->meta['cashierLabel'] ?? 'Semua Kasir/Sumber');
        $generatedAt = (string) ($this->meta['generatedAt'] ?? '');

        $s = $this->summary;

        $data = [];
        $data[] = [$storeName, null, null, null, null, null, null, null, null, null];
        $data[] = [$reportTitle, null, null, null, null, null, null, null, null, null];
        $data[] = ['Periode', $periodLabel, null, null, null, null, null, null, null, null];
        $data[] = ['Kasir/Sumber', $cashierLabel, null, null, null, null, null, null, null, null];
        $data[] = ['Dibuat', $generatedAt, null, null, null, null, null, null, null, null];
        $data[] = [null, null, null, null, null, null, null, null, null, null];
        $data[] = ['Ringkasan', null, null, null, null, null, null, null, null, null];
        $data[] = ['Total Transaksi', (int) ($s['txCount'] ?? 0), null, null, null, null, null, null, null, null];
        $data[] = ['Omzet (Net Sales)', (float) ($s['netRevenue'] ?? 0), null, null, null, null, null, null, null, null];
        $data[] = ['Item Terjual', (int) ($s['itemsSold'] ?? 0), null, null, null, null, null, null, null, null];
        $data[] = ['Rata-rata Order', (float) ($s['avgOrder'] ?? 0), null, null, null, null, null, null, null, null];
        $data[] = [null, null, null, null, null, null, null, null, null, null];
        $data[] = ['Detail', null, null, null, null, null, null, null, null, null];
        $data[] = ['Kode', 'Tanggal', 'Pelanggan', 'Telepon', 'Kasir/Sumber', 'Metode Bayar', 'Tipe Order', 'Status', 'Produk', 'Total'];

        foreach ($this->rows as $r) {
            $data[] = [
                (string) ($r['code'] ?? ''),
                (string) ($r['created_at'] ?? ''),
                (string) ($r['customer_name'] ?? ''),
                (string) ($r['phone'] ?? ''),
                (string) ($r['cashier_name'] ?? ''),
                (string) ($r['payment_method'] ?? ''),
                (string) ($r['order_type'] ?? ''),
                (string) ($r['payment_status'] ?? ''),
                (string) ($r['products'] ?? ''),
                (float) ($r['total'] ?? 0),
            ];
        }

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:J1');
                $sheet->mergeCells('A2:J2');

                $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A3:B5')->getFont()->setBold(true);

                $sheet->getStyle('A7:A7')->getFont()->setBold(true);
                $sheet->getStyle('A13:A13')->getFont()->setBold(true);

                $headerRow = 14;
                $sheet->getStyle("A{$headerRow}:J{$headerRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:J{$headerRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F4F6');
                $sheet->getStyle("A{$headerRow}:J{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->freezePane('A15');
            },
        ];
    }
}
