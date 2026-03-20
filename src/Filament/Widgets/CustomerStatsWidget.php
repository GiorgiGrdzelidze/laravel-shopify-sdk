<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use LaravelShopifySdk\Helpers\CurrencyHelper;
use LaravelShopifySdk\Models\Core\Customer;
use LaravelShopifySdk\Models\Orders\Order;
use LaravelShopifySdk\Models\Core\Store;

class CustomerStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $store = Store::where('status', 'active')->first();
        $currency = $store?->currency ?? 'USD';

        $stats = Cache::remember('customer_stats_widget', 60, function () {
            $totalCustomers = Customer::count();
            $newThisMonth = Customer::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            $newThisWeek = Customer::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

            $totalSpent = Order::sum('total_price');
            $avgSpentPerCustomer = $totalCustomers > 0 ? $totalSpent / $totalCustomers : 0;

            return [
                'total_customers' => $totalCustomers,
                'new_this_month' => $newThisMonth,
                'new_this_week' => $newThisWeek,
                'avg_spent' => $avgSpentPerCustomer,
            ];
        });

        return [
            Stat::make('Total Customers', number_format($stats['total_customers']))
                ->description('All synced customers')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('New This Month', number_format($stats['new_this_month']))
                ->description($stats['new_this_week'] . ' this week')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success'),

            Stat::make('Avg Spent', CurrencyHelper::format($stats['avg_spent'], $currency))
                ->description('Per customer average')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),
        ];
    }
}
