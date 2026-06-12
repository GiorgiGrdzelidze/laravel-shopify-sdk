<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament;

use Filament\Panel;
use LaravelShopifySdk\Filament\Pages\Analytics;
use LaravelShopifySdk\Filament\Resources\CollectionResource;
use LaravelShopifySdk\Filament\Resources\CustomerResource;
use LaravelShopifySdk\Filament\Resources\DiscountResource;
use LaravelShopifySdk\Filament\Resources\DraftOrderResource;
use LaravelShopifySdk\Filament\Resources\FulfillmentResource;
use LaravelShopifySdk\Filament\Resources\MetafieldResource;
use LaravelShopifySdk\Filament\Resources\OrderResource;
use LaravelShopifySdk\Filament\Resources\PermissionResource;
use LaravelShopifySdk\Filament\Resources\ProductResource;
use LaravelShopifySdk\Filament\Resources\ProductTagResource;
use LaravelShopifySdk\Filament\Resources\ProductTypeResource;
use LaravelShopifySdk\Filament\Resources\RoleResource;
use LaravelShopifySdk\Filament\Resources\ShopifyLogResource;
use LaravelShopifySdk\Filament\Resources\StoreResource;
use LaravelShopifySdk\Filament\Resources\UserResource;
use LaravelShopifySdk\Filament\Widgets\CustomerStatsWidget;
use LaravelShopifySdk\Filament\Widgets\InventoryAlertWidget;
use LaravelShopifySdk\Filament\Widgets\OrdersChartWidget;
use LaravelShopifySdk\Filament\Widgets\OrderStatsWidget;
use LaravelShopifySdk\Filament\Widgets\ProductsChartWidget;
use LaravelShopifySdk\Filament\Widgets\ProductStatsWidget;
use LaravelShopifySdk\Filament\Widgets\ShopifyStatsWidget;
use LaravelShopifySdk\Filament\Widgets\StoreOverviewWidget;
use LaravelShopifySdk\Filament\Widgets\SyncHealthWidget;
use LaravelShopifySdk\Filament\Widgets\TopProductsWidget;
use ReflectionClass;

/**
 * One-line Filament panel registration for the Shopify SDK.
 *
 * In your consumer app's AdminPanelProvider::panel() method:
 *
 *     use LaravelShopifySdk\Filament\ShopifyPanel;
 *
 *     public function panel(Panel $panel): Panel
 *     {
 *         return ShopifyPanel::registerOn(
 *             $panel
 *                 ->default()
 *                 ->id('admin')
 *                 ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
 *                 // ... your own config
 *         );
 *     }
 *
 * Reads `config('shopify.filament')` for per-resource/page/widget toggles
 * and optional navigation group override. Silently returns the panel
 * untouched if `enabled` is false.
 */
class ShopifyPanel
{
    /**
     * @var array<string, class-string>
     */
    public const RESOURCES = [
        'store' => StoreResource::class,
        'product' => ProductResource::class,
        'order' => OrderResource::class,
        'draft_order' => DraftOrderResource::class,
        'fulfillment' => FulfillmentResource::class,
        'discount' => DiscountResource::class,
        'customer' => CustomerResource::class,
        'collection' => CollectionResource::class,
        'metafield' => MetafieldResource::class,
        'product_type' => ProductTypeResource::class,
        'product_tag' => ProductTagResource::class,
        'shopify_log' => ShopifyLogResource::class,
        'user' => UserResource::class,
        'role' => RoleResource::class,
        'permission' => PermissionResource::class,
    ];

    /**
     * Default registration state per resource. User/Role/Permission default
     * to false because most consumer apps ship their own RBAC.
     *
     * @var array<string, bool>
     */
    public const RESOURCE_DEFAULTS = [
        'store' => true,
        'product' => true,
        'order' => true,
        'draft_order' => true,
        'fulfillment' => true,
        'discount' => true,
        'customer' => true,
        'collection' => true,
        'metafield' => true,
        'product_type' => true,
        'product_tag' => true,
        'shopify_log' => true,
        'user' => false,
        'role' => false,
        'permission' => false,
    ];

    /**
     * @var array<string, class-string>
     */
    public const PAGES = [
        'analytics' => Analytics::class,
    ];

    /**
     * @var array<string, bool>
     */
    public const PAGE_DEFAULTS = [
        'analytics' => true,
    ];

    /**
     * @var array<string, class-string>
     */
    public const WIDGETS = [
        'orders_chart' => OrdersChartWidget::class,
        'products_chart' => ProductsChartWidget::class,
        'order_stats' => OrderStatsWidget::class,
        'product_stats' => ProductStatsWidget::class,
        'customer_stats' => CustomerStatsWidget::class,
        'shopify_stats' => ShopifyStatsWidget::class,
        'top_products' => TopProductsWidget::class,
        'inventory_alert' => InventoryAlertWidget::class,
        'store_overview' => StoreOverviewWidget::class,
        'sync_health' => SyncHealthWidget::class,
    ];

    /**
     * @var array<string, bool>
     */
    public const WIDGET_DEFAULTS = [
        'orders_chart' => true,
        'products_chart' => true,
        'order_stats' => true,
        'product_stats' => true,
        'customer_stats' => true,
        'shopify_stats' => true,
        'top_products' => true,
        'inventory_alert' => true,
        'store_overview' => true,
        'sync_health' => true,
    ];

    public static function registerOn(Panel $panel): Panel
    {
        if (! config('shopify.filament.enabled', false)) {
            return $panel;
        }

        $resources = static::enabledResources();

        static::applyNavigationGroup($resources);

        return $panel
            ->resources($resources)
            ->pages(static::enabledPages())
            ->widgets(static::enabledWidgets());
    }

    /**
     * @return array<int, class-string>
     */
    public static function enabledResources(): array
    {
        return static::filterEnabled(static::RESOURCES, static::RESOURCE_DEFAULTS, 'shopify.filament.resources');
    }

    /**
     * @return array<int, class-string>
     */
    public static function enabledPages(): array
    {
        return static::filterEnabled(static::PAGES, static::PAGE_DEFAULTS, 'shopify.filament.pages');
    }

    /**
     * @return array<int, class-string>
     */
    public static function enabledWidgets(): array
    {
        return static::filterEnabled(static::WIDGETS, static::WIDGET_DEFAULTS, 'shopify.filament.widgets');
    }

    /**
     * @param  array<string, class-string>  $registry
     * @param  array<string, bool>  $defaults
     * @return array<int, class-string>
     */
    protected static function filterEnabled(array $registry, array $defaults, string $configKey): array
    {
        $toggles = array_replace($defaults, config($configKey, []));

        $enabled = [];
        foreach ($registry as $key => $class) {
            if (($toggles[$key] ?? false) === true) {
                $enabled[] = $class;
            }
        }

        return $enabled;
    }

    /**
     * Apply the config('shopify.filament.navigation_group') override to every
     * enabled resource + the Analytics page, via reflection on the static
     * `$navigationGroup` property. Skipped silently if no override is set.
     *
     * @param  array<int, class-string>  $resources
     */
    protected static function applyNavigationGroup(array $resources): void
    {
        $override = config('shopify.filament.navigation_group');
        if ($override === null || $override === '') {
            return;
        }

        $targets = array_merge($resources, static::enabledPages());

        foreach ($targets as $cls) {
            try {
                (new ReflectionClass($cls))
                    ->setStaticPropertyValue('navigationGroup', $override);
            } catch (\Throwable) {
                // Class may not declare $navigationGroup — skip.
            }
        }
    }
}
