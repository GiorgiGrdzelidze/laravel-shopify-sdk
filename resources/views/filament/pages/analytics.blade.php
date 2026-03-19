<x-filament-panels::page>
    <div class="space-y-12">
        {{-- Orders Section --}}
        @if ($this->getOrderWidgets())
            <section>
                <h2 class="text-lg font-bold text-gray-700 dark:text-gray-200 mb-6">Orders</h2>
                <x-filament-widgets::widgets
                    :widgets="$this->getOrderWidgets()"
                    :columns="2"
                />
            </section>
        @endif

        {{-- Products Section --}}
        @if ($this->getProductWidgets())
            <section>
                <h2 class="text-lg font-bold text-gray-700 dark:text-gray-200 mb-6">Products</h2>
                <x-filament-widgets::widgets
                    :widgets="$this->getProductWidgets()"
                    :columns="2"
                />
            </section>
        @endif

        {{-- Customers Section --}}
        @if ($this->getCustomerWidgets())
            <section>
                <h2 class="text-lg font-bold text-gray-700 dark:text-gray-200 mb-6">Customers</h2>
                <x-filament-widgets::widgets
                    :widgets="$this->getCustomerWidgets()"
                    :columns="1"
                />
            </section>
        @endif

        {{-- Inventory Section --}}
        @if ($this->getInventoryWidgets())
            <section>
                <h2 class="text-lg font-bold text-gray-700 dark:text-gray-200 mb-6">Inventory</h2>
                <x-filament-widgets::widgets
                    :widgets="$this->getInventoryWidgets()"
                    :columns="1"
                />
            </section>
        @endif
    </div>
</x-filament-panels::page>
