<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use LaravelShopifySdk\Helpers\CurrencyHelper;
use LaravelShopifySdk\Models\Orders\Order;
use LaravelShopifySdk\Models\Core\Store;

class OrderStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $store = Store::where('status', 'active')->first();
        $currency = $store?->currency ?? 'USD';

        $stats = Cache::remember('order_stats_widget', 60, function () {
            $totalOrders = Order::count();
            $totalRevenue = Order::sum('total_price');

            $todayOrders = Order::whereDate('processed_at', today())->count();
            $todayRevenue = Order::whereDate('processed_at', today())->sum('total_price');

            $thisWeekOrders = Order::whereBetween('processed_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
            $thisWeekRevenue = Order::whereBetween('processed_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('total_price');

            $thisMonthOrders = Order::whereMonth('processed_at', now()->month)
                ->whereYear('processed_at', now()->year)
                ->count();
            $thisMonthRevenue = Order::whereMonth('processed_at', now()->month)
                ->whereYear('processed_at', now()->year)
                ->sum('total_price');

            $pendingOrders = Order::where('fulfillment_status', 'unfulfilled')->count();
            $fulfilledOrders = Order::where('fulfillment_status', 'fulfilled')->count();

            $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

            return [
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'today_orders' => $todayOrders,
                'today_revenue' => $todayRevenue,
                'week_orders' => $thisWeekOrders,
                'week_revenue' => $thisWeekRevenue,
                'month_orders' => $thisMonthOrders,
                'month_revenue' => $thisMonthRevenue,
                'pending_orders' => $pendingOrders,
                'fulfilled_orders' => $fulfilledOrders,
                'avg_order_value' => $avgOrderValue,
            ];
        });

        return [
            Stat::make('Total Orders', number_format($stats['total_orders']))
                ->description($stats['today_orders'] . ' today')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('Total Revenue', CurrencyHelper::format($stats['total_revenue'], $currency))
                ->description(CurrencyHelper::format($stats['today_revenue'], $currency) . ' today')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('This Week', number_format($stats['week_orders']) . ' orders')
                ->description(CurrencyHelper::format($stats['week_revenue'], $currency))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('This Month', number_format($stats['month_orders']) . ' orders')
                ->description(CurrencyHelper::format($stats['month_revenue'], $currency))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning'),

            Stat::make('Avg Order Value', CurrencyHelper::format($stats['avg_order_value'], $currency))
                ->description('Per order average')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('gray'),

            Stat::make('Pending', number_format($stats['pending_orders']))
                ->description($stats['fulfilled_orders'] . ' fulfilled')
                ->descriptionIcon('heroicon-m-clock')
                ->color($stats['pending_orders'] > 0 ? 'warning' : 'success'),
        ];
    }
}
