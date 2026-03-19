<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Action</span>
            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->action }}</p>
        </div>
        <div>
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</span>
            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->status }}</p>
        </div>
        <div>
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Entity Type</span>
            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->entity_type }}</p>
        </div>
        <div>
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Entity ID</span>
            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->entity_id ?? '—' }}</p>
        </div>
        <div>
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Store</span>
            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->store?->shop_domain ?? '—' }}</p>
        </div>
        <div>
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Time</span>
            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->created_at->format('M d, Y H:i:s') }}</p>
        </div>
    </div>

    @if($record->message)
    <div>
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Message</span>
        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->message }}</p>
    </div>
    @endif

    @if($record->context)
    <div>
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Context</span>
        <pre class="mt-1 p-3 bg-gray-100 dark:bg-gray-800 rounded-lg text-xs overflow-x-auto">{{ json_encode($record->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
    @endif
</div>
