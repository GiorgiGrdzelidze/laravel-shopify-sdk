<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use LaravelShopifySdk\Models\Core\Product;
use LaravelShopifySdk\Models\Core\Variant;

class ProductStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $stats = Cache::remember('product_stats_widget', 60, function () {
            $totalProducts = Product::count();
            $activeProducts = Product::where('status', 'ACTIVE')->count();
            $draftProducts = Product::where('status', 'DRAFT')->count();
            $archivedProducts = Product::where('status', 'ARCHIVED')->count();
            $totalVariants = Variant::count();

            $lowStockCount = 0;
            $outOfStockCount = 0;

            // Calculate inventory stats from variants with inventory data
            Variant::whereHas('product', fn ($q) => $q->where('status', 'ACTIVE'))
                ->chunk(500, function ($variants) use (&$lowStockCount, &$outOfStockCount) {
                    foreach ($variants as $variant) {
                        $qty = $variant->inventory_quantity;
                        if ($qty !== null) {
                            if ($qty <= 0) {
                                $outOfStockCount++;
                            } elseif ($qty <= 5) {
                                $lowStockCount++;
                            }
                        }
                    }
                });

            return [
                'total_products' => $totalProducts,
                'active_products' => $activeProducts,
                'draft_products' => $draftProducts,
                'archived_products' => $archivedProducts,
                'total_variants' => $totalVariants,
                'low_stock' => $lowStockCount,
                'out_of_stock' => $outOfStockCount,
            ];
        });

        return [
            Stat::make('Total Products', number_format($stats['total_products']))
                ->description('All synced products')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Active', number_format($stats['active_products']))
                ->description(round(($stats['active_products'] / max($stats['total_products'], 1)) * 100) . '% of total')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Draft', number_format($stats['draft_products']))
                ->description('Unpublished products')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('warning'),

            Stat::make('Archived', number_format($stats['archived_products']))
                ->description('Hidden products')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('gray'),

            Stat::make('Variants', number_format($stats['total_variants']))
                ->description('Product variants')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('info'),

            Stat::make('Low Stock', number_format($stats['low_stock']))
                ->description($stats['out_of_stock'] . ' out of stock')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($stats['out_of_stock'] > 0 ? 'danger' : ($stats['low_stock'] > 0 ? 'warning' : 'success')),
        ];
    }
}
