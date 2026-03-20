<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use LaravelShopifySdk\Helpers\CurrencyHelper;
use LaravelShopifySdk\Models\Orders\OrderLine;
use LaravelShopifySdk\Models\Core\Store;

class TopProductsWidget extends Widget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    protected string $view = 'shopify::filament.widgets.top-products';

    public function getTopProducts(): array
    {
        $tableName = (new OrderLine())->getTable();

        return DB::table($tableName)
            ->selectRaw('title, SUM(quantity) as total_quantity, SUM(quantity * price) as total_revenue')
            ->whereNotNull('title')
            ->groupBy('title')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function getCurrency(): string
    {
        $store = Store::where('status', 'active')->first();
        return $store?->currency ?? 'USD';
    }
}
