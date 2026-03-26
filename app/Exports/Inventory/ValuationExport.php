<?php

namespace App\Exports\Inventory;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ValuationExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    public function __construct(
        private readonly array $meta,
        private readonly array $summary,
        private readonly array $rows,
    ) {}

    public function array(): array
    {
        $storeName = (string) ($this->meta['storeName'] ?? config('app.name'));
        $reportTitle = (string) ($this->meta['reportTitle'] ?? 'Laporan Persediaan');
        $periodLabel = (string) ($this->meta['periodLabel'] ?? '');
        $generatedAt = (string) ($this->meta['generatedAt'] ?? '');
        $badges = (array) ($this->meta['badges'] ?? []);

        $s = $this->summary;

        $data = [];
        $data[] = [$storeName, null, null, null, null, null, null, null];
        $data[] = [$reportTitle, null, null, null, null, null, null, null];
        $data[] = ['Periode', $periodLabel, null, null, null, null, null, null];
        $data[] = ['Dibuat', $generatedAt, null, null, null, null, null, null];
        if ($badges !== []) {
            $data[] = ['Catatan', implode(' · ', array_map('strval', $badges)), null, null, null, null, null, null];
        }
        $data[] = [null, null, null, null, null, null, null, null];
        $data[] = ['Ringkasan', null, null, null, null, null, null, null];
        $data[] = ['Total Qty', (float) ($s['qtyTotal'] ?? 0), null, null, null, null, null, null];
        $data[] = ['Total Nilai', (float) ($s['valueTotal'] ?? 0), null, null, null, null, null, null];
        $data[] = ['Coverage Unit Cost', ((float) ($s['coveragePercent'] ?? 0)) / 100, null, null, null, null, null, null];
        $data[] = ['Baris Ditampilkan', (int) ($s['shownCount'] ?? count($this->rows)), null, null, null, null, null, null];
        $data[] = ['Total Baris', (int) ($s['totalCount'] ?? count($this->rows)), null, null, null, null, null, null];
        $data[] = [null, null, null, null, null, null, null, null];
        $data[] = ['Detail', null, null, null, null, null, null, null];
        $data[] = ['SKU', 'Nama', 'Unit', 'Stok', 'Cost Price', 'Nilai Stok', 'Movement Lines', 'Coverage'];

        foreach ($this->rows as $r) {
            $row = is_array($r) ? $r : (array) $r;

            $movementLines = (int) ($row['movement_lines'] ?? 0);
            $movementCostLines = (int) ($row['movement_cost_lines'] ?? 0);
            $coverage = $movementLines > 0 ? ($movementCostLines / $movementLines) : 0.0;

            $data[] = [
                (string) ($row['sku'] ?? ''),
                (string) ($row['name'] ?? ''),
                (string) ($row['unit'] ?? ''),
                (float) ($row['stock_on_hand'] ?? 0),
                (float) ($row['cost_price'] ?? 0),
                (float) ($row['stock_value'] ?? 0),
                $movementLines,
                $coverage,
            ];
        }

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER,
            'H' => NumberFormat::FORMAT_PERCENTAGE_00,
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A2:H2');

                $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A3:B5')->getFont()->setBold(true);

                $sheet->getStyle('A7:A7')->getFont()->setBold(true);
                $sheet->getStyle('A14:A14')->getFont()->setBold(true);

                $headerRow = 15;
                $sheet->getStyle("A{$headerRow}:H{$headerRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:H{$headerRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F4F6');
                $sheet->getStyle("A{$headerRow}:H{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->freezePane('A16');
            },
        ];
    }
}
