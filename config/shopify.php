<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shopify API Version
    |--------------------------------------------------------------------------
    |
    | The Shopify API version to use. Shopify guarantees stable versions for
    | a minimum of 12 months. Update this value when upgrading API versions.
    | Format: YYYY-MM (e.g., '2024-01', '2024-04')
    |
    */
    'api_version' => env('SHOPIFY_API_VERSION', '2026-04'),

    /*
    |--------------------------------------------------------------------------
    | Single Store Mode (Optional)
    |--------------------------------------------------------------------------
    |
    | For single-store setups, you can provide static credentials here.
    | If these are set, the package will operate in single-store mode.
    | For multi-store OAuth mode, leave these empty.
    |
    */
    'single_store' => [
        'enabled' => env('SHOPIFY_SINGLE_STORE_ENABLED', false),
        'shop_domain' => env('SHOPIFY_SHOP_DOMAIN', null),
        'access_token' => env('SHOPIFY_ACCESS_TOKEN', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth Configuration
    |--------------------------------------------------------------------------
    |
    | OAuth settings for multi-store installations.
    | Required for OAuth Authorization Code Grant flow.
    |
    */
    'oauth' => [
        'client_id' => env('SHOPIFY_CLIENT_ID', null),
        'client_secret' => env('SHOPIFY_CLIENT_SECRET', null),
        'scopes' => env('SHOPIFY_SCOPES', 'read_products,write_products,read_orders,write_orders,read_customers,write_customers,read_inventory,write_inventory'),
        'redirect_uri' => env('SHOPIFY_REDIRECT_URI', '/shopify/callback'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the routes used by the package for OAuth and webhooks.
    |
    */
    'routes' => [
        'prefix' => env('SHOPIFY_ROUTE_PREFIX', 'shopify'),
        'middleware' => ['web'],
        'install_path' => '/install',
        'callback_path' => '/callback',
        'webhook_path' => '/webhooks',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook handling behavior.
    |
    */
    'webhooks' => [
        'secret' => env('SHOPIFY_WEBHOOK_SECRET', null),
        'queue' => env('SHOPIFY_WEBHOOK_QUEUE', 'default'),
        'process_async' => env('SHOPIFY_WEBHOOK_ASYNC', true),

        // Topics to automatically register (optional)
        'auto_register' => [
            'app/uninstalled',
            'products/create',
            'products/update',
            'products/delete',
            'orders/create',
            'orders/updated',
            'orders/cancelled',
            'customers/create',
            'customers/update',
            'inventory_levels/update',
        ],

        // Topic → Handler class mapping. Each handler must implement WebhookHandlerInterface.
        // Add your own handlers here to process specific webhook topics.
        'handlers' => [
            'app/uninstalled' => \LaravelShopifySdk\Webhooks\Handlers\AppUninstalledHandler::class,
            // 'orders/create' => \App\Webhooks\OrderCreatedHandler::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Client Configuration
    |--------------------------------------------------------------------------
    |
    | HTTP client settings for API requests.
    |
    */
    'client' => [
        'timeout' => env('SHOPIFY_TIMEOUT', 30),
        'connect_timeout' => env('SHOPIFY_CONNECT_TIMEOUT', 10),
        'retry_times' => env('SHOPIFY_RETRY_TIMES', 3),
        'retry_delay' => env('SHOPIFY_RETRY_DELAY', 1000), // milliseconds
        'max_backoff' => env('SHOPIFY_MAX_BACKOFF', 32000), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limit handling for REST and GraphQL APIs.
    | GraphQL uses cost-based throttling; REST uses bucket-based.
    |
    */
    'rate_limits' => [
        'rest' => [
            'max_requests' => 40,
            'leak_rate' => 2, // requests per second
        ],
        'graphql' => [
            'max_cost' => 1000,
            'restore_rate' => 50, // points per second
            'throttle_on_cost' => 800, // start throttling at this cost
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Mirroring Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which entities to mirror and sync behavior.
    |
    */
    'sync' => [
        'enabled' => env('SHOPIFY_SYNC_ENABLED', true),
        'chunk_size' => env('SHOPIFY_SYNC_CHUNK_SIZE', 250),
        'queue' => env('SHOPIFY_SYNC_QUEUE', 'default'),

        'entities' => [
            'products' => true,
            'variants' => true,
            'orders' => true,
            'customers' => true,
            'locations' => true,
            'inventory_levels' => true,
        ],

        // Default sync schedules (can be customized in scheduler)
        'schedules' => [
            'products' => 'daily',
            'orders' => 'hourly',
            'customers' => 'daily',
            'inventory' => 'hourly',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament v5 Integration
    |--------------------------------------------------------------------------
    |
    | Enable optional Filament v5 admin panel resources and widgets.
    | Requires filament/filament:^5.0 package to be installed.
    | Resources and widgets auto-discover when enabled.
    |
    */
    'filament' => [
        'enabled' => env('SHOPIFY_FILAMENT_ENABLED', false),
        'navigation_group' => 'Shopify',
        'navigation_sort' => 10,

        /*
        |--------------------------------------------------------------------------
        | Sandbox CRUD Mode (Testing Only)
        |--------------------------------------------------------------------------
        |
        | When enabled, allows create/edit/delete operations on mirrored entities
        | (Products, Orders, Customers) in Filament. This is for TESTING ONLY.
        | WARNING: Changes made in sandbox mode do NOT sync back to Shopify.
        |
        */
        'testing_crud_enabled' => env('SHOPIFY_TESTING_CRUD_ENABLED', false),

        'resources' => [
            'stores' => true,
            'products' => true,
            'variants' => true,
            'orders' => true,
            'customers' => true,
            'locations' => true,
            'inventory_levels' => true,
            'webhook_events' => true,
            'sync_runs' => true,
        ],

        'widgets' => [
            'sync_health' => true,
            'orders_stats' => true,
            'products_stats' => true,
        ],

        'cache' => [
            'widgets_ttl' => env('SHOPIFY_FILAMENT_CACHE_TTL', 300), // 5 minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure structured logging behavior.
    |
    */
    'logging' => [
        'channel' => env('SHOPIFY_LOG_CHANNEL', 'stack'),
        'level' => env('SHOPIFY_LOG_LEVEL', 'info'),
        'include_context' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    |
    | Customize table names if needed.
    |
    */
    'tables' => [
        'stores' => 'shopify_stores',
        'products' => 'shopify_products',
        'variants' => 'shopify_variants',
        'orders' => 'shopify_orders',
        'order_lines' => 'shopify_order_lines',
        'customers' => 'shopify_customers',
        'locations' => 'shopify_locations',
        'inventory_levels' => 'shopify_inventory_levels',
        'webhook_events' => 'shopify_webhook_events',
        'sync_runs' => 'shopify_sync_runs',
    ],
];
