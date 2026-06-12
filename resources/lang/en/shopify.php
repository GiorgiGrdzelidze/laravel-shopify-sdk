<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Flash messages
    |--------------------------------------------------------------------------
    */

    'store_connected' => 'Store connected successfully!',
    'store_disconnected' => 'Store disconnected.',
    'authentication_failed' => 'Authentication failed. Please try again.',
    'sync_started' => 'Sync started for :entity',
    'sync_completed' => 'Sync completed for :entity',
    'sync_failed' => 'Sync failed for :entity',
    'webhook_received' => 'Webhook received: :topic',
    'webhook_processed' => 'Webhook processed: :topic',

    /*
    |--------------------------------------------------------------------------
    | Filament resource labels
    |--------------------------------------------------------------------------
    |
    | Each resource pulls its singular / plural / navigation label from this
    | block via the HasShopifyLabels trait. Override via translation merge or
    | by publishing this file.
    |
    */

    'resources' => [
        'store' => ['singular' => 'Store', 'plural' => 'Stores', 'nav' => 'Stores'],
        'product' => ['singular' => 'Product', 'plural' => 'Products', 'nav' => 'Products'],
        'order' => ['singular' => 'Order', 'plural' => 'Orders', 'nav' => 'Orders'],
        'draft_order' => ['singular' => 'Draft Order', 'plural' => 'Draft Orders', 'nav' => 'Draft Orders'],
        'fulfillment' => ['singular' => 'Fulfillment', 'plural' => 'Fulfillments', 'nav' => 'Fulfillments'],
        'discount' => ['singular' => 'Discount', 'plural' => 'Discounts', 'nav' => 'Discounts'],
        'customer' => ['singular' => 'Customer', 'plural' => 'Customers', 'nav' => 'Customers'],
        'collection' => ['singular' => 'Collection', 'plural' => 'Collections', 'nav' => 'Collections'],
        'metafield' => ['singular' => 'Metafield', 'plural' => 'Metafields', 'nav' => 'Metafields'],
        'product_type' => ['singular' => 'Product Type', 'plural' => 'Product Types', 'nav' => 'Product Types'],
        'product_tag' => ['singular' => 'Product Tag', 'plural' => 'Product Tags', 'nav' => 'Product Tags'],
        'shopify_log' => ['singular' => 'Activity Log', 'plural' => 'Activity Logs', 'nav' => 'Activity Logs'],
        'user' => ['singular' => 'User', 'plural' => 'Users', 'nav' => 'Users'],
        'role' => ['singular' => 'Role', 'plural' => 'Roles', 'nav' => 'Roles'],
        'permission' => ['singular' => 'Permission', 'plural' => 'Permissions', 'nav' => 'Permissions'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament navigation groups
    |--------------------------------------------------------------------------
    */

    'nav_groups' => [
        'shopify' => 'Shopify',
        'operations' => 'Operations',
        'marketing' => 'Marketing',
        'reports' => 'Reports',
        'access_control' => 'Access Control',
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament pages
    |--------------------------------------------------------------------------
    */

    'pages' => [
        'analytics' => ['title' => 'Analytics', 'nav' => 'Analytics'],
    ],
];
