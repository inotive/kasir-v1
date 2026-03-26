<?php

namespace App\View\Components\ecommerce;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class EcommerceMetrics extends Component
{
    public function __construct(
        public int $customers = 0,
        public float $customersDeltaPercent = 0,
        public bool $customersDeltaUp = true,
        public int $revenueAmount = 0,
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.ecommerce.ecommerce-metrics');
    }
}
