<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use LaravelShopifySdk\Models\Product;

class ProductsChartWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected static bool $isDiscovered = false;

    public function getHeading(): ?string
    {
        return 'Products by Status';
    }

    public function getMaxHeight(): ?string
    {
        return '250px';
    }

    protected function getData(): array
    {
        $active = Product::where('status', 'ACTIVE')->count();
        $draft = Product::where('status', 'DRAFT')->count();
        $archived = Product::where('status', 'ARCHIVED')->count();

        return [
            'datasets' => [
                [
                    'data' => [$active, $draft, $archived],
                    'backgroundColor' => ['#22c55e', '#f59e0b', '#ef4444'],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => ['Active', 'Draft', 'Archived'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'cutout' => '60%',
        ];
    }
}
