<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use LaravelShopifySdk\Models\Orders\Order;

class OrdersChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    public ?string $filter = '30';

    public function getHeading(): ?string
    {
        return 'Orders Overview';
    }

    public function getDescription(): ?string
    {
        return 'Order volume and revenue trends';
    }

    public function getMaxHeight(): ?string
    {
        return '320px';
    }

    protected function getFilters(): ?array
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 90 days',
            '365' => 'Last year',
        ];
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();

        // Single grouped query instead of N individual queries
        $dailyStats = Order::where('processed_at', '>=', $startDate)
            ->selectRaw('DATE(processed_at) as date, COUNT(*) as order_count, COALESCE(SUM(total_price), 0) as revenue')
            ->groupBy('date')
            ->pluck('revenue', 'date')
            ->toArray();

        $dailyCounts = Order::where('processed_at', '>=', $startDate)
            ->selectRaw('DATE(processed_at) as date, COUNT(*) as order_count')
            ->groupBy('date')
            ->pluck('order_count', 'date')
            ->toArray();

        $labels = [];
        $ordersData = [];
        $revenueData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateKey = $date->format('Y-m-d');
            $labels[] = $date->format($days > 30 ? 'M d' : 'D');
            $ordersData[] = (int) ($dailyCounts[$dateKey] ?? 0);
            $revenueData[] = (float) ($dailyStats[$dateKey] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $ordersData,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'transparent',
                    'borderWidth' => 2,
                    'fill' => false,
                    'tension' => 0.4,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 5,
                    'pointHoverBackgroundColor' => '#f59e0b',
                    'pointHoverBorderColor' => '#fff',
                    'pointHoverBorderWidth' => 2,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Revenue',
                    'data' => $revenueData,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'transparent',
                    'borderWidth' => 2,
                    'fill' => false,
                    'tension' => 0.4,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 5,
                    'pointHoverBackgroundColor' => '#22c55e',
                    'pointHoverBorderColor' => '#fff',
                    'pointHoverBorderWidth' => 2,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Orders',
                        'color' => '#9ca3af',
                    ],
                    'grid' => [
                        'color' => 'rgba(156, 163, 175, 0.08)',
                    ],
                    'ticks' => [
                        'color' => '#9ca3af',
                    ],
                    'border' => [
                        'display' => false,
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Revenue',
                        'color' => '#9ca3af',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                    'ticks' => [
                        'color' => '#9ca3af',
                    ],
                    'border' => [
                        'display' => false,
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'color' => '#9ca3af',
                        'maxTicksLimit' => 12,
                    ],
                    'border' => [
                        'display' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'align' => 'end',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'padding' => 20,
                        'color' => '#9ca3af',
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => '#1f2937',
                    'titleColor' => '#f9fafb',
                    'bodyColor' => '#d1d5db',
                    'borderColor' => 'rgba(255, 255, 255, 0.1)',
                    'borderWidth' => 1,
                    'cornerRadius' => 8,
                    'padding' => 12,
                    'displayColors' => true,
                    'usePointStyle' => true,
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
        ];
    }
}
