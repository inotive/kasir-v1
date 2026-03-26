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

class StockCardExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    public function __construct(
        private readonly array $meta,
        private readonly array $summary,
        private readonly array $rows,
        private readonly array $ingredient,
        private readonly float $startingBalance,
        private readonly float $startingValue,
    ) {}

    public function array(): array
    {
        $storeName = (string) ($this->meta['storeName'] ?? config('app.name'));
        $reportTitle = (string) ($this->meta['reportTitle'] ?? 'Kartu Stok');
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
        $data[] = ['Bahan', (string) ($this->ingredient['name'] ?? ''), null, null, null, null, null, null];
        $data[] = ['Unit', (string) ($this->ingredient['unit'] ?? ''), null, null, null, null, null, null];
        $data[] = ['Saldo Awal', $this->startingBalance, null, null, null, null, null, null];
        $data[] = ['Nilai Awal', $this->startingValue, null, null, null, null, null, null];
        $data[] = ['Baris Ditampilkan', (int) ($s['shownCount'] ?? count($this->rows)), null, null, null, null, null, null];
        $data[] = ['Total Baris', (int) ($s['totalCount'] ?? count($this->rows)), null, null, null, null, null, null];
        $data[] = [null, null, null, null, null, null, null, null];
        $data[] = ['Detail', null, null, null, null, null, null, null];
        $data[] = ['Waktu', 'Tipe', 'Qty', 'Unit Cost', 'Nilai', 'Saldo', 'Nilai Berjalan', 'Catatan'];

        foreach ($this->rows as $r) {
            $data[] = [
                (string) ($r['when'] ?? ''),
                (string) ($r['type'] ?? ''),
                (float) ($r['qty'] ?? 0),
                (float) ($r['unit_cost'] ?? 0),
                (float) ($r['delta_value'] ?? 0),
                (float) ($r['balance'] ?? 0),
                (float) ($r['running_value'] ?? 0),
                (string) ($r['note'] ?? ''),
            ];
        }

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_00,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_00,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
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

                $sheet->getStyle('A7:A12')->getFont()->setBold(true);
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
