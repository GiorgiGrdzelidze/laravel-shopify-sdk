<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $products = $this->getTopProducts();
            $currency = $this->getCurrency();
            $maxQuantity = collect($products)->max('total_quantity') ?: 1;
        @endphp

        <x-slot name="heading">
            <div class="flex items-center gap-2.5">
                <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-amber-500/10 dark:bg-amber-500/15">
                    <x-filament::icon
                        icon="heroicon-o-trophy"
                        class="w-4.5 h-4.5 text-amber-500"
                    />
                </span>
                <span class="font-semibold tracking-tight">Top Selling Products</span>
            </div>
        </x-slot>

        @if (blank($products))
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="flex items-center justify-center w-14 h-14 rounded-2xl bg-gray-100 dark:bg-white/5 mb-4">
                    <x-filament::icon
                        icon="heroicon-o-chart-bar"
                        class="w-7 h-7 text-gray-400 dark:text-gray-500"
                    />
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">No sales data available</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Sync orders to see top products</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach ($products as $index => $product)
                    @php
                        $percentage = round(($product->total_quantity / $maxQuantity) * 100);
                        $isTop3 = $index < 3;
                        $rankGradient = match ($index) {
                            0 => 'bg-gradient-to-br from-amber-400 to-amber-600 shadow-amber-500/25',
                            1 => 'bg-gradient-to-br from-gray-300 to-gray-500 shadow-gray-400/25',
                            2 => 'bg-gradient-to-br from-amber-600 to-amber-800 shadow-amber-700/25',
                            default => 'bg-gray-200 dark:bg-white/10',
                        };
                        $rankText = $isTop3 ? 'text-white font-bold' : 'text-gray-600 dark:text-gray-400 font-semibold';
                    @endphp

                    <div
                        class="analytics-card group flex items-center gap-4 p-3.5 rounded-2xl
                               bg-white/60 dark:bg-white/[0.03]
                               ring-1 ring-black/[0.04] dark:ring-white/[0.06]
                               hover:ring-primary-500/30 dark:hover:ring-primary-500/20
                               hover:-translate-y-0.5 hover:shadow-lg hover:shadow-black/[0.03] dark:hover:shadow-black/20
                               transition-all duration-200 ease-out"
                    >
                        {{-- Rank Badge --}}
                        <div class="flex-shrink-0">
                            <span class="{{ $rankGradient }} {{ $rankText }} text-xs w-8 h-8 rounded-full flex items-center justify-center {{ $isTop3 ? 'shadow-md' : '' }}"
                                  aria-label="Rank {{ $index + 1 }}">
                                #{{ $index + 1 }}
                            </span>
                        </div>

                        {{-- Product Info + Progress --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ $product->title }}
                            </p>
                            <div class="flex items-center gap-3 mt-2">
                                {{-- Progress Bar --}}
                                <div class="flex-1 h-1.5 rounded-full bg-gray-100 dark:bg-white/[0.06] overflow-hidden">
                                    <div class="progress-bar-fill h-full rounded-full bg-gradient-to-r from-primary-500 to-primary-400"
                                         style="width: {{ $percentage }}%; animation-delay: {{ $index * 0.08 }}s">
                                    </div>
                                </div>
                                <span class="flex-shrink-0 text-xs text-gray-400 dark:text-gray-500 tabular-nums">
                                    {{ $percentage }}%
                                </span>
                            </div>
                        </div>

                        {{-- Stats --}}
                        <div class="flex-shrink-0 text-right space-y-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white tabular-nums">
                                {{ \LaravelShopifySdk\Helpers\CurrencyHelper::format((float) $product->total_revenue, $currency) }}
                            </p>
                            <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-white/[0.06] px-2 py-0.5 text-xs tabular-nums text-gray-600 dark:text-gray-400">
                                {{ number_format($product->total_quantity) }} sold
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
