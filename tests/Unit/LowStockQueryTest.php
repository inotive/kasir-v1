<?php

namespace Tests\Unit;

use App\Livewire\Inventory\LowStockPage;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class LowStockQueryTest extends TestCase
{
    #[Test]
    public function it_does_not_use_having_for_low_stock_filter(): void
    {
        $component = new LowStockPage;

        $method = new ReflectionMethod($component, 'query');
        $method->setAccessible(true);

        $builder = $method->invoke($component);
        $sql = strtolower($builder->toSql());

        $this->assertStringNotContainsString(' having ', $sql);
        $this->assertStringContainsString('left join', $sql);
        $this->assertStringContainsString('inventory_stock', $sql);
    }
}
