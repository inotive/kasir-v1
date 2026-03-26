<?php

namespace App\Support\Finance;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class NetSales
{
    public static function postedPaymentStatuses(): array
    {
        return ['paid', 'settlement', 'capture', 'success', 'partial_refund', 'refunded'];
    }

    public static function itemNetExpr(string $alias = 'ti'): string
    {
        return $alias.'.subtotal - COALESCE('.$alias.'.voucher_discount_amount, 0) - COALESCE('.$alias.'.manual_discount_amount, 0)';
    }

    public static function netPerTransactionExpr(string $itemNetCol = 'item_net', string $refundedCol = 'refunded_amount'): string
    {
        $cappedRefund = '(CASE WHEN '.$refundedCol.' > '.$itemNetCol.' THEN '.$itemNetCol.' ELSE '.$refundedCol.' END)';
        $net = '('.$itemNetCol.' - '.$cappedRefund.')';

        return '(CASE WHEN '.$net.' < 0 THEN 0 ELSE '.$net.' END)';
    }

    public static function netSalesBetween(CarbonInterface $from, CarbonInterface $to): float
    {
        $sub = DB::table('transactions as t')
            ->join('transaction_items as ti', 't.id', '=', 'ti.transaction_id')
            ->whereIn('t.payment_status', self::postedPaymentStatuses())
            ->whereBetween('t.created_at', [$from, $to])
            ->selectRaw('t.id as tx_id')
            ->selectRaw('COALESCE(t.refunded_amount, 0) as refunded_amount')
            ->selectRaw('COALESCE(SUM('.self::itemNetExpr('ti').'), 0) as item_net')
            ->groupBy('tx_id', 'refunded_amount');

        $revenue = DB::query()
            ->fromSub($sub, 'x')
            ->selectRaw('COALESCE(SUM('.self::netPerTransactionExpr('x.item_net', 'x.refunded_amount').'), 0) as revenue')
            ->value('revenue');

        return (float) ($revenue ?? 0);
    }

    public static function netSalesByDay(CarbonInterface $from, CarbonInterface $to): array
    {
        $sub = DB::table('transactions as t')
            ->join('transaction_items as ti', 't.id', '=', 'ti.transaction_id')
            ->whereIn('t.payment_status', self::postedPaymentStatuses())
            ->whereBetween('t.created_at', [$from, $to])
            ->selectRaw('DATE(t.created_at) as bucket')
            ->selectRaw('t.id as tx_id')
            ->selectRaw('COALESCE(t.refunded_amount, 0) as refunded_amount')
            ->selectRaw('COALESCE(SUM('.self::itemNetExpr('ti').'), 0) as item_net')
            ->groupBy('bucket', 'tx_id', 'refunded_amount');

        $rows = DB::query()
            ->fromSub($sub, 'x')
            ->selectRaw('x.bucket as bucket')
            ->selectRaw('COALESCE(SUM('.self::netPerTransactionExpr('x.item_net', 'x.refunded_amount').'), 0) as revenue')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row->bucket] = (float) $row->revenue;
        }

        return $out;
    }

    public static function netSalesByMonth(CarbonInterface $from, CarbonInterface $to): array
    {
        $driver = DB::connection()->getDriverName();
        $bucketExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m-01', t.created_at)"
            : 'DATE_FORMAT(t.created_at, "%Y-%m-01")';

        $sub = DB::table('transactions as t')
            ->join('transaction_items as ti', 't.id', '=', 'ti.transaction_id')
            ->whereIn('t.payment_status', self::postedPaymentStatuses())
            ->whereBetween('t.created_at', [$from, $to])
            ->selectRaw($bucketExpr.' as bucket')
            ->selectRaw('t.id as tx_id')
            ->selectRaw('COALESCE(t.refunded_amount, 0) as refunded_amount')
            ->selectRaw('COALESCE(SUM('.self::itemNetExpr('ti').'), 0) as item_net')
            ->groupBy('bucket', 'tx_id', 'refunded_amount');

        $rows = DB::query()
            ->fromSub($sub, 'x')
            ->selectRaw('x.bucket as bucket')
            ->selectRaw('COALESCE(SUM('.self::netPerTransactionExpr('x.item_net', 'x.refunded_amount').'), 0) as revenue')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row->bucket] = (float) $row->revenue;
        }

        return $out;
    }
}
