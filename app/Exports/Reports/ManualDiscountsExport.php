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

class ManualDiscountsExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    public function __construct(
        private readonly array $meta,
        private readonly array $summary,
        private readonly array $rows,
    ) {}

    public function array(): array
    {
        $storeName = (string) ($this->meta['storeName'] ?? config('app.name'));
        $reportTitle = (string) ($this->meta['reportTitle'] ?? 'Laporan Diskon Manual');
        $periodLabel = (string) ($this->meta['periodLabel'] ?? '');
        $generatedAt = (string) ($this->meta['generatedAt'] ?? '');
        $badges = (array) ($this->meta['badges'] ?? []);

        $s = $this->summary;

        $data = [];
        $data[] = [$storeName, null, null, null, null, null];
        $data[] = [$reportTitle, null, null, null, null, null];
        $data[] = ['Periode', $periodLabel, null, null, null, null];
        $data[] = ['Dibuat', $generatedAt, null, null, null, null];
        if ($badges !== []) {
            $data[] = ['Filter', implode(' · ', array_map('strval', $badges)), null, null, null, null];
        }
        $data[] = [null, null, null, null, null, null];
        $data[] = ['Ringkasan', null, null, null, null, null];
        $data[] = ['Total Diskon', (float) ($s['totalDiscount'] ?? 0), null, null, null, null];
        $data[] = ['Jumlah Transaksi', (int) ($s['txCount'] ?? 0), null, null, null, null];
        $data[] = [null, null, null, null, null, null];
        $data[] = ['Detail', null, null, null, null, null];
        $data[] = ['Waktu', 'Kode', 'Channel', 'Total', 'Diskon', 'Kasir'];

        foreach ($this->rows as $r) {
            $data[] = [
                (string) ($r['created_at'] ?? ''),
                (string) ($r['code'] ?? ''),
                (string) ($r['channel'] ?? ''),
                (float) ($r['total'] ?? 0),
                (float) ($r['manual_discount_amount'] ?? 0),
                (string) ($r['cashier_name'] ?? ''),
            ];
        }

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'B' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:F1');
                $sheet->mergeCells('A2:F2');

                $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A3:B5')->getFont()->setBold(true);

                $sheet->getStyle('A7:A7')->getFont()->setBold(true);
                $sheet->getStyle('A11:A11')->getFont()->setBold(true);

                $headerRow = 12;
                $sheet->getStyle("A{$headerRow}:F{$headerRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:F{$headerRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F4F6');
                $sheet->getStyle("A{$headerRow}:F{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->freezePane('A13');
            },
        ];
    }
}
