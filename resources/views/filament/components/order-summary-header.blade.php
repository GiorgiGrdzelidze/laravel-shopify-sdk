@php
    use LaravelShopifySdk\Models\Order;
    use LaravelShopifySdk\Models\Store;
    use LaravelShopifySdk\Helpers\CurrencyHelper;

    $currency = Store::first()?->currency ?? 'USD';

    // Get current table filters from Livewire component
    $filters = $this->tableFilters ?? [];

    // Check if any filter has a value (handles different filter structures)
    $hasFilters = false;
    foreach ($filters as $filterName => $filterData) {
        if (is_array($filterData)) {
            foreach ($filterData as $key => $value) {
                if (!empty($value)) {
                    $hasFilters = true;
                    break 2;
                }
            }
        } elseif (!empty($filterData)) {
            $hasFilters = true;
            break;
        }
    }

    // Build query based on filters
    $query = Order::query();

    // Apply store filter
    if (!empty($filters['store_id']['value'])) {
        $query->where('store_id', $filters['store_id']['value']);
    }

    // Apply financial status filter
    if (!empty($filters['financial_status']['values'])) {
        $query->whereIn('financial_status', $filters['financial_status']['values']);
    }

    // Apply fulfillment status filter
    if (!empty($filters['fulfillment_status']['values'])) {
        $query->whereIn('fulfillment_status', $filters['fulfillment_status']['values']);
    }

    // Apply date filters
    if (!empty($filters['processed_at']['processed_from'])) {
        $query->whereDate('processed_at', '>=', $filters['processed_at']['processed_from']);
    }
    if (!empty($filters['processed_at']['processed_until'])) {
        $query->whereDate('processed_at', '<=', $filters['processed_at']['processed_until']);
    }

    $filteredCount = $query->count();
    $filteredTotal = $query->sum('total_price');
    $filteredAvg = $filteredCount > 0 ? $filteredTotal / $filteredCount : 0;

    // Total stats (all orders)
    $totalCount = Order::count();
    $totalSum = Order::sum('total_price');
@endphp

@if($hasFilters)
<div style="margin: 16px 0 16px 0; padding: 0 0 0 16px;">
    <div style="display: flex; flex-wrap: wrap; gap: 12px;">
        {{-- Filtered Results Card --}}
        <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; background: linear-gradient(to right, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.1)); border: 1px solid rgba(59, 130, 246, 0.2);">
            <div style="padding: 8px; border-radius: 8px; background: rgba(59, 130, 246, 0.2);">
                <svg style="width: 20px; height: 20px; color: #3b82f6;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                </svg>
            </div>
            <div>
                <p style="font-size: 11px; font-weight: 500; color: #6b7280; margin: 0;">Filtered Orders</p>
                <p style="font-size: 18px; font-weight: 700; color: #2563eb; margin: 0;">{{ number_format($filteredCount) }}</p>
            </div>
        </div>

        {{-- Total Revenue Card --}}
        <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; background: linear-gradient(to right, rgba(34, 197, 94, 0.1), rgba(22, 163, 74, 0.1)); border: 1px solid rgba(34, 197, 94, 0.2);">
            <div style="padding: 8px; border-radius: 8px; background: rgba(34, 197, 94, 0.2);">
                <svg style="width: 20px; height: 20px; color: #22c55e;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                </svg>
            </div>
            <div>
                <p style="font-size: 11px; font-weight: 500; color: #6b7280; margin: 0;">Total Revenue</p>
                <p style="font-size: 18px; font-weight: 700; color: #16a34a; margin: 0;">{{ CurrencyHelper::format($filteredTotal, $currency) }}</p>
            </div>
        </div>

        {{-- Average Order Card --}}
        <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; background: linear-gradient(to right, rgba(6, 182, 212, 0.1), rgba(8, 145, 178, 0.1)); border: 1px solid rgba(6, 182, 212, 0.2);">
            <div style="padding: 8px; border-radius: 8px; background: rgba(6, 182, 212, 0.2);">
                <svg style="width: 20px; height: 20px; color: #06b6d4;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V13.5Zm0 2.25h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V18Zm2.498-6.75h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V13.5Zm0 2.25h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V18Zm2.504-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5Zm0 2.25h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V18Zm2.498-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5ZM8.25 6h7.5v2.25h-7.5V6ZM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 0 0 2.25 2.25h10.5a2.25 2.25 0 0 0 2.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0 0 12 2.25Z" />
                </svg>
            </div>
            <div>
                <p style="font-size: 11px; font-weight: 500; color: #6b7280; margin: 0;">Average Order</p>
                <p style="font-size: 18px; font-weight: 700; color: #0891b2; margin: 0;">{{ CurrencyHelper::format($filteredAvg, $currency) }}</p>
            </div>
        </div>

        {{-- Percentage of Total --}}
        <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; background: linear-gradient(to right, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.1)); border: 1px solid rgba(245, 158, 11, 0.2);">
            <div style="padding: 8px; border-radius: 8px; background: rgba(245, 158, 11, 0.2);">
                <svg style="width: 20px; height: 20px; color: #f59e0b;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />
                </svg>
            </div>
            <div>
                <p style="font-size: 11px; font-weight: 500; color: #6b7280; margin: 0;">% of All Orders</p>
                <p style="font-size: 18px; font-weight: 700; color: #d97706; margin: 0;">{{ $totalCount > 0 ? number_format(($filteredCount / $totalCount) * 100, 1) : 0 }}%</p>
            </div>
        </div>
    </div>
</div>
@endif
