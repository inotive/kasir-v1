<?php

namespace App\Http\Middleware;

use App\Models\DiningTable;
use Closure;
use Illuminate\Http\Request;

class CheckTableNumber
{
    public function handle(Request $request, Closure $next)
    {
        $tableId = $request->session()->get('dining_table_id');

        if ($tableId && DiningTable::query()->whereKey($tableId)->exists()) {
            if (
                ! $request->session()->get('customer_ready')
                && ! $request->is('order/start')
                && ! $request->is('order/verify-email')
            ) {
                return redirect()->route('self-order.start');
            }

            return $next($request);
        }

        $request->session()->forget([
            'dining_table_id',
            'customer_ready',
            'customer_type',
            'member_id',
            'name',
            'phone',
            'email',
        ]);

        return redirect()->route('self-order.invalid');
    }
}
