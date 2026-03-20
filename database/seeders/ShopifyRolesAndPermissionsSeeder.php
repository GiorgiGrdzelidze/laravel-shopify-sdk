<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Database\Seeders;

use Illuminate\Database\Seeder;
use LaravelShopifySdk\Models\Access\Permission;
use LaravelShopifySdk\Models\Access\Role;

class ShopifyRolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Stores
            ['name' => 'View Stores', 'slug' => 'stores.view', 'group' => 'stores'],
            ['name' => 'Create Stores', 'slug' => 'stores.create', 'group' => 'stores'],
            ['name' => 'Edit Stores', 'slug' => 'stores.edit', 'group' => 'stores'],
            ['name' => 'Delete Stores', 'slug' => 'stores.delete', 'group' => 'stores'],

            // Products
            ['name' => 'View Products', 'slug' => 'products.view', 'group' => 'products'],
            ['name' => 'Create Products', 'slug' => 'products.create', 'group' => 'products'],
            ['name' => 'Edit Products', 'slug' => 'products.edit', 'group' => 'products'],
            ['name' => 'Delete Products', 'slug' => 'products.delete', 'group' => 'products'],
            ['name' => 'Push Products to Shopify', 'slug' => 'products.push', 'group' => 'products'],
            ['name' => 'Pull Products from Shopify', 'slug' => 'products.pull', 'group' => 'products'],

            // Collections
            ['name' => 'View Collections', 'slug' => 'collections.view', 'group' => 'collections'],
            ['name' => 'Create Collections', 'slug' => 'collections.create', 'group' => 'collections'],
            ['name' => 'Edit Collections', 'slug' => 'collections.edit', 'group' => 'collections'],
            ['name' => 'Delete Collections', 'slug' => 'collections.delete', 'group' => 'collections'],
            ['name' => 'Push Collections to Shopify', 'slug' => 'collections.push', 'group' => 'collections'],

            // Orders
            ['name' => 'View Orders', 'slug' => 'orders.view', 'group' => 'orders'],
            ['name' => 'Create Orders', 'slug' => 'orders.create', 'group' => 'orders'],
            ['name' => 'Edit Orders', 'slug' => 'orders.edit', 'group' => 'orders'],
            ['name' => 'Delete Orders', 'slug' => 'orders.delete', 'group' => 'orders'],
            ['name' => 'Refund Orders', 'slug' => 'orders.refund', 'group' => 'orders'],

            // Customers
            ['name' => 'View Customers', 'slug' => 'customers.view', 'group' => 'customers'],
            ['name' => 'Create Customers', 'slug' => 'customers.create', 'group' => 'customers'],
            ['name' => 'Edit Customers', 'slug' => 'customers.edit', 'group' => 'customers'],
            ['name' => 'Delete Customers', 'slug' => 'customers.delete', 'group' => 'customers'],

            // Inventory
            ['name' => 'View Inventory', 'slug' => 'inventory.view', 'group' => 'inventory'],
            ['name' => 'Edit Inventory', 'slug' => 'inventory.edit', 'group' => 'inventory'],
            ['name' => 'Transfer Inventory', 'slug' => 'inventory.transfer', 'group' => 'inventory'],

            // Metafields
            ['name' => 'View Metafields', 'slug' => 'metafields.view', 'group' => 'metafields'],
            ['name' => 'Create Metafields', 'slug' => 'metafields.create', 'group' => 'metafields'],
            ['name' => 'Edit Metafields', 'slug' => 'metafields.edit', 'group' => 'metafields'],
            ['name' => 'Delete Metafields', 'slug' => 'metafields.delete', 'group' => 'metafields'],

            // Discounts
            ['name' => 'View Discounts', 'slug' => 'discounts.view', 'group' => 'discounts'],
            ['name' => 'Create Discounts', 'slug' => 'discounts.create', 'group' => 'discounts'],
            ['name' => 'Edit Discounts', 'slug' => 'discounts.edit', 'group' => 'discounts'],
            ['name' => 'Delete Discounts', 'slug' => 'discounts.delete', 'group' => 'discounts'],

            // Draft Orders
            ['name' => 'View Draft Orders', 'slug' => 'draft_orders.view', 'group' => 'draft_orders'],
            ['name' => 'Create Draft Orders', 'slug' => 'draft_orders.create', 'group' => 'draft_orders'],
            ['name' => 'Edit Draft Orders', 'slug' => 'draft_orders.edit', 'group' => 'draft_orders'],
            ['name' => 'Delete Draft Orders', 'slug' => 'draft_orders.delete', 'group' => 'draft_orders'],
            ['name' => 'Complete Draft Orders', 'slug' => 'draft_orders.complete', 'group' => 'draft_orders'],
            ['name' => 'Send Invoices', 'slug' => 'draft_orders.invoice', 'group' => 'draft_orders'],

            // Fulfillment
            ['name' => 'View Fulfillments', 'slug' => 'fulfillments.view', 'group' => 'fulfillments'],
            ['name' => 'Create Fulfillments', 'slug' => 'fulfillments.create', 'group' => 'fulfillments'],
            ['name' => 'Edit Fulfillments', 'slug' => 'fulfillments.edit', 'group' => 'fulfillments'],
            ['name' => 'Cancel Fulfillments', 'slug' => 'fulfillments.cancel', 'group' => 'fulfillments'],

            // Webhooks
            ['name' => 'View Webhooks', 'slug' => 'webhooks.view', 'group' => 'webhooks'],
            ['name' => 'Create Webhooks', 'slug' => 'webhooks.create', 'group' => 'webhooks'],
            ['name' => 'Delete Webhooks', 'slug' => 'webhooks.delete', 'group' => 'webhooks'],

            // Sync
            ['name' => 'Run Sync', 'slug' => 'sync.run', 'group' => 'sync'],
            ['name' => 'View Sync Logs', 'slug' => 'sync.logs', 'group' => 'sync'],

            // Logs & Activity
            ['name' => 'View Activity Logs', 'slug' => 'logs.view', 'group' => 'logs'],
            ['name' => 'Export Logs', 'slug' => 'logs.export', 'group' => 'logs'],

            // Reports & Analytics
            ['name' => 'View Reports', 'slug' => 'reports.view', 'group' => 'reports'],
            ['name' => 'Export Reports', 'slug' => 'reports.export', 'group' => 'reports'],

            // Bulk Operations
            ['name' => 'Bulk Edit Products', 'slug' => 'bulk.products', 'group' => 'bulk'],
            ['name' => 'Bulk Edit Inventory', 'slug' => 'bulk.inventory', 'group' => 'bulk'],
            ['name' => 'Import Data', 'slug' => 'bulk.import', 'group' => 'bulk'],
            ['name' => 'Export Data', 'slug' => 'bulk.export', 'group' => 'bulk'],

            // Settings / Access Control
            ['name' => 'Manage Roles', 'slug' => 'settings.roles', 'group' => 'settings'],
            ['name' => 'Manage Permissions', 'slug' => 'settings.permissions', 'group' => 'settings'],
            ['name' => 'Manage Users', 'slug' => 'settings.users', 'group' => 'settings'],
            ['name' => 'Manage Settings', 'slug' => 'settings.general', 'group' => 'settings'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // Create roles
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Full access to all features and settings',
                'permissions' => [], // Super admin has all permissions by default in the trait
            ],
            [
                'name' => 'Store Manager',
                'slug' => 'store-manager',
                'description' => 'Full access to assigned stores',
                'permissions' => [
                    'stores.view', 'stores.edit',
                    'products.view', 'products.create', 'products.edit', 'products.delete', 'products.push', 'products.pull',
                    'collections.view', 'collections.create', 'collections.edit', 'collections.delete', 'collections.push',
                    'orders.view', 'orders.create', 'orders.edit', 'orders.refund',
                    'customers.view', 'customers.create', 'customers.edit',
                    'inventory.view', 'inventory.edit', 'inventory.transfer',
                    'metafields.view', 'metafields.create', 'metafields.edit', 'metafields.delete',
                    'discounts.view', 'discounts.create', 'discounts.edit', 'discounts.delete',
                    'draft_orders.view', 'draft_orders.create', 'draft_orders.edit', 'draft_orders.delete', 'draft_orders.complete', 'draft_orders.invoice',
                    'fulfillments.view', 'fulfillments.create', 'fulfillments.edit',
                    'webhooks.view',
                    'sync.run', 'sync.logs',
                    'logs.view',
                    'reports.view', 'reports.export',
                    'bulk.products', 'bulk.inventory', 'bulk.export',
                ],
            ],
            [
                'name' => 'Product Manager',
                'slug' => 'product-manager',
                'description' => 'Manage products, collections, and inventory',
                'permissions' => [
                    'stores.view',
                    'products.view', 'products.create', 'products.edit', 'products.push', 'products.pull',
                    'collections.view', 'collections.create', 'collections.edit', 'collections.push',
                    'inventory.view', 'inventory.edit',
                    'metafields.view', 'metafields.create', 'metafields.edit',
                    'sync.run',
                    'bulk.products', 'bulk.inventory',
                ],
            ],
            [
                'name' => 'Order Manager',
                'slug' => 'order-manager',
                'description' => 'Manage orders, fulfillments, and customers',
                'permissions' => [
                    'stores.view',
                    'orders.view', 'orders.edit', 'orders.refund',
                    'customers.view', 'customers.edit',
                    'draft_orders.view', 'draft_orders.create', 'draft_orders.edit', 'draft_orders.complete', 'draft_orders.invoice',
                    'fulfillments.view', 'fulfillments.create', 'fulfillments.edit',
                    'discounts.view', 'discounts.create', 'discounts.edit',
                ],
            ],
            [
                'name' => 'Marketing Manager',
                'slug' => 'marketing-manager',
                'description' => 'Manage discounts, collections, and reports',
                'permissions' => [
                    'stores.view',
                    'products.view',
                    'collections.view', 'collections.create', 'collections.edit',
                    'discounts.view', 'discounts.create', 'discounts.edit', 'discounts.delete',
                    'reports.view', 'reports.export',
                ],
            ],
            [
                'name' => 'Fulfillment Staff',
                'slug' => 'fulfillment-staff',
                'description' => 'Process orders and fulfillments',
                'permissions' => [
                    'stores.view',
                    'orders.view',
                    'inventory.view',
                    'fulfillments.view', 'fulfillments.create', 'fulfillments.edit',
                ],
            ],
            [
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access to all data',
                'is_default' => true,
                'permissions' => [
                    'stores.view',
                    'products.view',
                    'collections.view',
                    'orders.view',
                    'customers.view',
                    'inventory.view',
                    'discounts.view',
                    'fulfillments.view',
                    'sync.logs',
                    'logs.view',
                    'reports.view',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'] ?? [];
            unset($roleData['permissions']);

            $role = Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );

            if (!empty($permissions)) {
                $permissionIds = Permission::whereIn('slug', $permissions)->pluck('id')->toArray();
                $role->permissions()->syncWithoutDetaching($permissionIds);
            }
        }
    }
}
