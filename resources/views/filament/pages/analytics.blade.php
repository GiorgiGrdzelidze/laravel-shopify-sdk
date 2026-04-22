<x-filament-panels::page>
    <div class="space-y-10">

        {{-- Orders Section --}}
        @if ($this->getOrderWidgets())
            <section>
                <div class="flex items-center gap-3 mb-6">
                    <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-primary-500/10 dark:bg-primary-500/15">
                        <x-filament::icon
                            icon="heroicon-o-shopping-cart"
                            class="w-5 h-5 text-primary-500"
                        />
                    </span>
                    <h2 class="text-lg font-semibold tracking-tight text-gray-900 dark:text-white">Orders</h2>
                </div>
                <div class="border-t border-gray-200/60 dark:border-white/5 pt-6">
                    <x-filament-widgets::widgets
                        :widgets="$this->getOrderWidgets()"
                        :columns="2"
                    />
                </div>
            </section>
        @endif

        {{-- Products Section --}}
        @if ($this->getProductWidgets())
            <section>
                <div class="flex items-center gap-3 mb-6">
                    <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/15">
                        <x-filament::icon
                            icon="heroicon-o-cube"
                            class="w-5 h-5 text-emerald-500"
                        />
                    </span>
                    <h2 class="text-lg font-semibold tracking-tight text-gray-900 dark:text-white">Products</h2>
                </div>
                <div class="border-t border-gray-200/60 dark:border-white/5 pt-6">
                    <x-filament-widgets::widgets
                        :widgets="$this->getProductWidgets()"
                        :columns="2"
                    />
                </div>
            </section>
        @endif

        {{-- Customers Section --}}
        @if ($this->getCustomerWidgets())
            <section>
                <div class="flex items-center gap-3 mb-6">
                    <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-violet-500/10 dark:bg-violet-500/15">
                        <x-filament::icon
                            icon="heroicon-o-users"
                            class="w-5 h-5 text-violet-500"
                        />
                    </span>
                    <h2 class="text-lg font-semibold tracking-tight text-gray-900 dark:text-white">Customers</h2>
                </div>
                <div class="border-t border-gray-200/60 dark:border-white/5 pt-6">
                    <x-filament-widgets::widgets
                        :widgets="$this->getCustomerWidgets()"
                        :columns="1"
                    />
                </div>
            </section>
        @endif

        {{-- Inventory Section --}}
        @if ($this->getInventoryWidgets())
            <section>
                <div class="flex items-center gap-3 mb-6">
                    <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-sky-500/10 dark:bg-sky-500/15">
                        <x-filament::icon
                            icon="heroicon-o-cube-transparent"
                            class="w-5 h-5 text-sky-500"
                        />
                    </span>
                    <h2 class="text-lg font-semibold tracking-tight text-gray-900 dark:text-white">Inventory</h2>
                </div>
                <div class="border-t border-gray-200/60 dark:border-white/5 pt-6">
                    <x-filament-widgets::widgets
                        :widgets="$this->getInventoryWidgets()"
                        :columns="1"
                    />
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
