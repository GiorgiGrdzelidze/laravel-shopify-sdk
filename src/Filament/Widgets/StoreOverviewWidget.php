<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use LaravelShopifySdk\Helpers\CurrencyHelper;
use LaravelShopifySdk\Models\Store;

class StoreOverviewWidget extends BaseWidget
{
    public ?Model $record = null;

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        if (!$this->record instanceof Store) {
            return [];
        }

        $store = $this->record;

        $totalProducts = $store->products()->count();
        $activeProducts = $store->products()->where('status', 'ACTIVE')->count();
        $totalVariants = \LaravelShopifySdk\Models\Variant::where('store_id', $store->id)->count();
        $totalOrders = $store->orders()->count();
        $totalRevenue = $store->orders()->sum('total_price');
        $totalCustomers = $store->customers()->count();
        $totalLocations = $store->locations()->count();

        $ordersThisMonth = $store->orders()
            ->whereMonth('processed_at', now()->month)
            ->whereYear('processed_at', now()->year)
            ->count();

        $revenueThisMonth = $store->orders()
            ->whereMonth('processed_at', now()->month)
            ->whereYear('processed_at', now()->year)
            ->sum('total_price');

        $currency = $store->currency ?? 'USD';

        return [
            Stat::make('Products', number_format($totalProducts))
                ->description($activeProducts . ' active')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info')
                ->chart($this->getProductsChart($store)),

            Stat::make('Variants', number_format($totalVariants))
                ->description('Product variants')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('primary'),

            Stat::make('Orders', number_format($totalOrders))
                ->description($ordersThisMonth . ' this month')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning')
                ->chart($this->getOrdersChart($store)),

            Stat::make('Revenue', CurrencyHelper::format($totalRevenue, $currency))
                ->description(CurrencyHelper::format($revenueThisMonth, $currency) . ' this month')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart($this->getRevenueChart($store)),

            Stat::make('Customers', number_format($totalCustomers))
                ->description('Total customers')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),

            Stat::make('Locations', number_format($totalLocations))
                ->description('Inventory locations')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('danger'),
        ];
    }

    protected function getProductsChart(Store $store): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = $store->products()
                ->whereDate('created_at', '<=', $date)
                ->count();
        }
        return $data;
    }

    protected function getOrdersChart(Store $store): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = $store->orders()
                ->whereDate('processed_at', $date)
                ->count();
        }
        return $data;
    }

    protected function getRevenueChart(Store $store): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = (float) $store->orders()
                ->whereDate('processed_at', $date)
                ->sum('total_price');
        }
        return $data;
    }

}
