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

class SalesProfitExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    public function __construct(
        private readonly array $meta,
        private readonly array $summary,
        private readonly array $dailyRows,
    ) {}

    public function array(): array
    {
        $storeName = (string) ($this->meta['storeName'] ?? config('app.name'));
        $reportTitle = (string) ($this->meta['reportTitle'] ?? 'Laporan Penjualan & Laba');
        $periodLabel = (string) ($this->meta['periodLabel'] ?? '');
        $generatedAt = (string) ($this->meta['generatedAt'] ?? '');

        $s = $this->summary;

        $rows = [];
        $rows[] = [$storeName, null, null, null, null, null, null];
        $rows[] = [$reportTitle, null, null, null, null, null, null];
        $rows[] = ['Periode', $periodLabel, null, null, null, null, null];
        $rows[] = ['Dibuat', $generatedAt, null, null, null, null, null];
        $rows[] = [null, null, null, null, null, null, null];
        $rows[] = ['Ringkasan', null, null, null, null, null, null];
        $rows[] = ['Transaksi', (int) ($s['txCount'] ?? 0), null, null, null, null, null];
        $rows[] = ['Omzet (Net Sales)', (float) ($s['revenue'] ?? 0), null, null, null, null, null];
        $rows[] = ['COGS Penjualan', (float) ($s['cogsSales'] ?? ($s['cogsInventory'] ?? 0)), null, null, null, null, null];
        $rows[] = ['Loss Stok (Net)', (float) ($s['stockLossNet'] ?? 0), null, null, null, null, null];
        $rows[] = ['Total COGS + Loss', (float) ($s['cogsTotal'] ?? 0), null, null, null, null, null];
        $rows[] = ['Laba Kotor', (float) ($s['grossProfit'] ?? 0), null, null, null, null, null];
        $rows[] = ['Margin Kotor', ((float) ($s['grossMarginPercent'] ?? 0)) / 100, null, null, null, null, null];
        $rows[] = ['Beban Operasional', (float) ($s['operatingExpenseTotal'] ?? 0), null, null, null, null, null];
        $rows[] = ['Laba Bersih', (float) ($s['netProfit'] ?? 0), null, null, null, null, null];
        $rows[] = ['Margin Bersih', ((float) ($s['netMarginPercent'] ?? 0)) / 100, null, null, null, null, null];
        $rows[] = ['Avg Order', (float) ($s['avgOrder'] ?? 0), null, null, null, null, null];
        $rows[] = [null, null, null, null, null, null, null];
        $rows[] = ['Harian', null, null, null, null, null, null];
        $rows[] = ['Tanggal', 'Omzet', 'COGS Penjualan', 'Loss Stok (Net)', 'Total COGS', 'Laba Kotor', 'Margin'];

        foreach ($this->dailyRows as $r) {
            $revenue = (float) ($r['revenue'] ?? 0);
            $grossProfit = (float) ($r['gross_profit'] ?? $r['grossProfit'] ?? 0);

            $rows[] = [
                (string) ($r['day'] ?? ''),
                $revenue,
                (float) ($r['cogs_sales'] ?? $r['cogsSales'] ?? $r['cogs_inventory'] ?? 0),
                (float) ($r['stock_loss_net'] ?? $r['stockLossNet'] ?? 0),
                (float) ($r['cogs_total'] ?? $r['cogsTotal'] ?? $r['cogs'] ?? 0),
                $grossProfit,
                $revenue > 0 ? ($grossProfit / $revenue) : 0.0,
            ];
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_PERCENTAGE_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:G1');
                $sheet->mergeCells('A2:G2');

                $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A3:B4')->getFont()->setBold(true);

                $sheet->getStyle('A6:A6')->getFont()->setBold(true);
                $sheet->getStyle('A19:A19')->getFont()->setBold(true);

                $headerRow = 20;
                $sheet->getStyle("A{$headerRow}:G{$headerRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:G{$headerRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F4F6');
                $sheet->getStyle("A{$headerRow}:G{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->freezePane('A21');
            },
        ];
    }
}
