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

class MemberPerformanceExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    public function __construct(
        private readonly array $meta,
        private readonly array $summary,
        private readonly array $topMembers,
        private readonly bool $canViewPii,
    ) {}

    public function array(): array
    {
        $storeName = (string) ($this->meta['storeName'] ?? config('app.name'));
        $reportTitle = (string) ($this->meta['reportTitle'] ?? 'Laporan Performa Member');
        $periodLabel = (string) ($this->meta['periodLabel'] ?? '');
        $generatedAt = (string) ($this->meta['generatedAt'] ?? '');
        $badges = (array) ($this->meta['badges'] ?? []);

        $s = $this->summary;

        $rows = [];
        $rows[] = [$storeName, null, null, null, null, null, null, null, null];
        $rows[] = [$reportTitle, null, null, null, null, null, null, null, null];
        $rows[] = ['Periode', $periodLabel, null, null, null, null, null, null, null];
        $rows[] = ['Dibuat', $generatedAt, null, null, null, null, null, null, null];
        if ($badges !== []) {
            $rows[] = ['Catatan', implode(' · ', array_map('strval', $badges)), null, null, null, null, null, null, null];
        }
        $rows[] = [null, null, null, null, null, null, null, null, null];
        $rows[] = ['Ringkasan', null, null, null, null, null, null, null, null];
        $rows[] = ['Revenue Member', (float) ($s['memberRevenue'] ?? 0), null, null, null, null, null, null, null];
        $rows[] = ['Profit Member', (float) ($s['memberProfit'] ?? 0), null, null, null, null, null, null, null];
        $rows[] = ['Margin Member', ((float) ($s['memberMarginPercent'] ?? 0)) / 100, null, null, null, null, null, null, null];
        $rows[] = ['Transaksi Member', (int) ($s['memberTxCount'] ?? 0), null, null, null, null, null, null, null];
        $rows[] = ['Member Aktif', (int) ($s['activeMembers'] ?? 0), null, null, null, null, null, null, null];
        $rows[] = ['Repeat Rate', ((float) ($s['repeatRatePercent'] ?? 0)) / 100, null, null, null, null, null, null, null];
        $rows[] = ['Avg Order', (float) ($s['avgOrder'] ?? 0), null, null, null, null, null, null, null];
        $rows[] = ['Share Revenue Member', ((float) ($s['memberSharePercent'] ?? 0)) / 100, null, null, null, null, null, null, null];
        $rows[] = [null, null, null, null, null, null, null, null, null];
        $rows[] = ['Top Member', null, null, null, null, null, null, null, null];

        $header = ['Member', $this->canViewPii ? 'Telepon' : 'Telepon', 'Transaksi', 'Item', 'Revenue', 'HPP', 'Profit', 'Margin', 'Transaksi Terakhir'];
        if (! $this->canViewPii) {
            $header[1] = '-';
        }
        $rows[] = $header;

        foreach ($this->topMembers as $r) {
            $revenue = (float) ($r['revenue'] ?? 0);
            $margin = (float) ($r['margin_percent'] ?? 0);

            $rows[] = [
                (string) ($r['member_name'] ?? ''),
                $this->canViewPii ? (string) ($r['member_phone'] ?? '') : '',
                (int) ($r['tx_count'] ?? 0),
                (int) ($r['qty'] ?? 0),
                $revenue,
                (float) ($r['hpp'] ?? 0),
                (float) ($r['profit'] ?? 0),
                $margin / 100,
                (string) ($r['last_purchase_at'] ?? ''),
            ];
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_NUMBER,
            'D' => NumberFormat::FORMAT_NUMBER,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_PERCENTAGE_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:I1');
                $sheet->mergeCells('A2:I2');

                $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A3:B5')->getFont()->setBold(true);

                $sheet->getStyle('A7:A7')->getFont()->setBold(true);
                $sheet->getStyle('A17:A17')->getFont()->setBold(true);

                $headerRow = 18;
                $sheet->getStyle("A{$headerRow}:I{$headerRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:I{$headerRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F4F6');
                $sheet->getStyle("A{$headerRow}:I{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->freezePane('A19');
            },
        ];
    }
}
