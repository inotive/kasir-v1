<?php

namespace App\View\Components\ecommerce;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatisticsChart extends Component
{
    public function __construct(
        public array $series = [
            ['name' => 'Revenue', 'data' => [1800000, 1900000, 1700000, 1600000, 1750000, 1650000, 1700000, 2050000, 2300000, 2100000, 2400000, 2350000]],
        ],
        public array $categories = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        public ?string $from = null,
        public ?string $to = null,
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.ecommerce.statistics-chart');
    }
}
