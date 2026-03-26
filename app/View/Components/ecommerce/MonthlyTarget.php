<?php

namespace App\View\Components\ecommerce;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class MonthlyTarget extends Component
{
    public function __construct(
        public float $progressPercent = 75.55,
        public float $deltaPercent = 10.0,
        public bool $deltaUp = true,
        public int $targetAmount = 20000,
        public int $revenueAmount = 20000,
        public int $todayAmount = 20000,
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.ecommerce.monthly-target');
    }
}
