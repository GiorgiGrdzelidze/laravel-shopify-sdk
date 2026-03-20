<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use LaravelShopifySdk\Helpers\CurrencyHelper;
use LaravelShopifySdk\Models\Core\Customer;
use LaravelShopifySdk\Models\Orders\Order;
use LaravelShopifySdk\Models\Core\Product;
use LaravelShopifySdk\Models\Core\Store;

class ShopifyStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $cacheTtl = config('shopify.filament.cache.widgets_ttl', 300);

        $store = Store::where('status', 'active')->first();
        $currency = $store?->currency ?? 'USD';

        $stats = Cache::remember('shopify_stats_widget', $cacheTtl, function () {
            return [
                'active_stores' => Store::where('status', 'active')->count(),
                'total_products' => Product::count(),
                'total_orders' => Order::count(),
                'total_customers' => Customer::count(),
                'total_revenue' => Order::sum('total_price'),
                'orders_today' => Order::whereDate('processed_at', today())->count(),
                'revenue_today' => Order::whereDate('processed_at', today())->sum('total_price'),
            ];
        });

        return [
            Stat::make('Active Stores', $stats['active_stores'])
                ->description('Connected Shopify stores')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('success'),

            Stat::make('Total Products', number_format($stats['total_products']))
                ->description('Synced from Shopify')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),

            Stat::make('Total Orders', number_format($stats['total_orders']))
                ->description($stats['orders_today'] . ' today')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning'),

            Stat::make('Total Customers', number_format($stats['total_customers']))
                ->description('Unique customers')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Total Revenue', CurrencyHelper::format($stats['total_revenue'], $currency))
                ->description(CurrencyHelper::format($stats['revenue_today'], $currency) . ' today')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }

    public static function canView(): bool
    {
        return config('shopify.filament.widgets.orders_stats', true);
    }
}
