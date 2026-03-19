<x-filament-widgets::widget>
    <x-filament::section heading="Top Selling Products" class="overflow-hidden">
        @php
            $products = $this->getTopProducts();
            $currency = $this->getCurrency();
            $maxQuantity = collect($products)->max('total_quantity') ?: 1;
        @endphp

        @if (blank($products))
            <div class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                No sales data available
            </div>
        @else
            <div class="-mx-6 -mb-6 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                        <thead class="bg-gray-50/80 dark:bg-white/5">
                            <tr>
                                <th class="w-16 px-4 py-3 text-left text-sm font-semibold text-gray-950 dark:text-white">
                                    #
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-950 dark:text-white">
                                    Product
                                </th>

                                <th class="w-44 px-4 py-3 text-left text-sm font-semibold text-gray-950 dark:text-white">
                                    Rating
                                </th>

                                <th
                                    class="w-24 px-4 py-3 text-left text-sm font-semibold text-gray-950 dark:text-white">
                                    Sold
                                </th>

                                <th
                                    class="w-36 px-4 py-3 text-right text-sm font-semibold text-gray-950 dark:text-white">
                                    Revenue
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-white/10">
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
                                @endphp

                                <tr class="transition hover:bg-gray-50/70 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 align-top text-sm text-gray-950 dark:text-white">
                                        {{ $index + 1 }}
                                    </td>

                                    <td class="px-4 py-3 align-top">
                                        <div class="max-w-[720px]">
                                            <div
                                                class="truncate text-[15px] font-medium leading-6 text-gray-950 dark:text-white">
                                                {{ $product->title }}
                                            </div>

                                            <div class="text-sm leading-5 text-gray-500 dark:text-gray-400">
                                                {{ number_format($percentage, 0) }}% of top seller
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 align-top">
                                        <div class="flex items-center gap-1">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <span class="text-base leading-none"
                                                    style="color: {{ $i <= $stars ? '#f59e0b' : '#d1d5db' }};">
                                                    ★
                                                </span>
                                            @endfor
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 align-top text-sm font-medium text-gray-950 dark:text-white">
                                        {{ number_format($product->total_quantity) }}
                                    </td>

                                    <td
                                        class="px-4 py-3 align-top text-right text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ \LaravelShopifySdk\Helpers\CurrencyHelper::format((float) $product->total_revenue, $currency) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
