<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $products = $this->getTopProducts();
            $currency = $this->getCurrency();
            $maxQuantity = collect($products)->max('total_quantity') ?: 1;
        @endphp

        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon
                    icon="heroicon-o-trophy"
                    class="h-5 w-5 text-amber-500"
                />
                <span>Top Selling Products</span>
            </div>
        </x-slot>

        @if (blank($products))
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <x-filament::icon
                    icon="heroicon-o-chart-bar"
                    class="h-12 w-12 text-gray-400 dark:text-gray-500 mb-4"
                />
                <p class="text-sm text-gray-500 dark:text-gray-400">No sales data available</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($products as $index => $product)
                    @php
                        $percentage = ($product->total_quantity / $maxQuantity) * 100;
                        $stars = match (true) {
                            $percentage >= 80 => 5,
                            $percentage >= 60 => 4,
                            $percentage >= 40 => 3,
                            $percentage >= 20 => 2,
                            default => 1,
                        };
                        $rankColor = match ($index) {
                            0 => 'bg-amber-500',
                            1 => 'bg-gray-400',
                            2 => 'bg-amber-700',
                            default => 'bg-gray-300 dark:bg-gray-600',
                        };
                    @endphp

                    <div class="flex items-center gap-4 p-3 rounded-xl bg-gray-50 dark:bg-white/5 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors">
                        {{-- Rank Badge --}}
                        <div class="flex-shrink-0">
                            <span class="{{ $rankColor }} text-white text-xs font-bold w-7 h-7 rounded-full flex items-center justify-center shadow-sm">
                                {{ $index + 1 }}
                            </span>
                        </div>

                        {{-- Product Info --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                {{ $product->title }}
                            </p>
                            <div class="flex items-center gap-2 mt-1">
                                {{-- Star Rating --}}
                                <div class="flex items-center gap-0.5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="w-3.5 h-3.5 {{ $i <= $stars ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ number_format($percentage, 0) }}% of top
                                </span>
                            </div>
                        </div>

                        {{-- Stats --}}
                        <div class="flex-shrink-0 text-right">
                            <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400">
                                {{ \LaravelShopifySdk\Helpers\CurrencyHelper::format((float) $product->total_revenue, $currency) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ number_format($product->total_quantity) }} sold
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
