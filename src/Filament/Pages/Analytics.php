<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Pages;

use Filament\Pages\Page;
use LaravelShopifySdk\Filament\NavigationGroup;
use LaravelShopifySdk\Filament\NavigationIcon;
use LaravelShopifySdk\Filament\Traits\HasShopifyPermissions;
use LaravelShopifySdk\Filament\Widgets\CustomerStatsWidget;
use LaravelShopifySdk\Filament\Widgets\InventoryAlertWidget;
use LaravelShopifySdk\Filament\Widgets\OrdersChartWidget;
use LaravelShopifySdk\Filament\Widgets\OrderStatsWidget;
use LaravelShopifySdk\Filament\Widgets\ProductsChartWidget;
use LaravelShopifySdk\Filament\Widgets\ProductStatsWidget;
use LaravelShopifySdk\Filament\Widgets\TopProductsWidget;

class Analytics extends Page
{
    use HasShopifyPermissions;

    protected static string|\BackedEnum|null $navigationIcon = NavigationIcon::OutlinedChartBar;

    protected string $view = 'shopify::filament.pages.analytics';

    protected static ?string $title = 'Analytics';

    protected static ?string $navigationLabel = 'Analytics';

    protected static ?int $navigationSort = 10;

    protected static \UnitEnum|string|null $navigationGroup = NavigationGroup::Shopify;

    protected static function getPermissionPrefix(): string
    {
        return 'analytics';
    }

    public static function canAccess(): bool
    {
        return static::checkPermission(static::getPermissionPrefix() . '.view');
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function getOrderWidgets(): array
    {
        return [
            OrderStatsWidget::class,
            OrdersChartWidget::class,
        ];
    }

    public function getProductWidgets(): array
    {
        return [
            ProductStatsWidget::class,
            TopProductsWidget::class,
        ];
    }

    public function getCustomerWidgets(): array
    {
        return [
            CustomerStatsWidget::class,
        ];
    }

    public function getInventoryWidgets(): array
    {
        return [
            InventoryAlertWidget::class,
        ];
    }
}
