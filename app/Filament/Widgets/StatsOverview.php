<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $product_count = Product::count();
        $order_count = Order::count();
        $revenue = Order::sum('total_price');
        $expense = Expense::sum('amount');

        return [
            Stat::make('product Total', $product_count),
            Stat::make('Order Total', $order_count),
            Stat::make('Revenue', 'Rp ' . number_format($revenue, '0', ',', '.')),
            Stat::make('Expense Total', 'Rp ' . number_format($expense, 0, ',', '.')),
        ];
    }
}
