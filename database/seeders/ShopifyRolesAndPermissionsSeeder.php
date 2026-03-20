<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Database\Seeders;

use Illuminate\Database\Seeder;
use LaravelShopifySdk\Models\Permission;
use LaravelShopifySdk\Models\Role;

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

            // Orders
            ['name' => 'View Orders', 'slug' => 'orders.view', 'group' => 'orders'],
            ['name' => 'Edit Orders', 'slug' => 'orders.edit', 'group' => 'orders'],
            ['name' => 'Delete Orders', 'slug' => 'orders.delete', 'group' => 'orders'],

            // Customers
            ['name' => 'View Customers', 'slug' => 'customers.view', 'group' => 'customers'],
            ['name' => 'Edit Customers', 'slug' => 'customers.edit', 'group' => 'customers'],
            ['name' => 'Delete Customers', 'slug' => 'customers.delete', 'group' => 'customers'],

            // Inventory
            ['name' => 'View Inventory', 'slug' => 'inventory.view', 'group' => 'inventory'],
            ['name' => 'Edit Inventory', 'slug' => 'inventory.edit', 'group' => 'inventory'],

            // Sync
            ['name' => 'Run Sync', 'slug' => 'sync.run', 'group' => 'sync'],
            ['name' => 'View Sync Logs', 'slug' => 'sync.logs', 'group' => 'sync'],

            // Settings / Access Control
            ['name' => 'Manage Roles', 'slug' => 'settings.roles', 'group' => 'settings'],
            ['name' => 'Manage Permissions', 'slug' => 'settings.permissions', 'group' => 'settings'],
            ['name' => 'Manage Users', 'slug' => 'settings.users', 'group' => 'settings'],
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
                    'orders.view', 'orders.edit',
                    'customers.view', 'customers.edit',
                    'inventory.view', 'inventory.edit',
                    'sync.run', 'sync.logs',
                ],
            ],
            [
                'name' => 'Product Manager',
                'slug' => 'product-manager',
                'description' => 'Manage products and inventory',
                'permissions' => [
                    'stores.view',
                    'products.view', 'products.create', 'products.edit', 'products.push', 'products.pull',
                    'inventory.view', 'inventory.edit',
                    'sync.run',
                ],
            ],
            [
                'name' => 'Order Manager',
                'slug' => 'order-manager',
                'description' => 'Manage orders and customers',
                'permissions' => [
                    'stores.view',
                    'orders.view', 'orders.edit',
                    'customers.view', 'customers.edit',
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
                    'orders.view',
                    'customers.view',
                    'inventory.view',
                    'sync.logs',
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
