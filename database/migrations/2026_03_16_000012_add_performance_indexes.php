<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $orderLinesTable = config('shopify.tables.order_lines', 'shopify_order_lines');
        $ordersTable = config('shopify.tables.orders', 'shopify_orders');
        $customersTable = config('shopify.tables.customers', 'shopify_customers');
        $syncRunsTable = config('shopify.tables.sync_runs', 'shopify_sync_runs');

        // Add indexes to order_lines for product/variant lookups (if not exists)
        if (!$this->hasIndex($orderLinesTable, 'product_id')) {
            Schema::table($orderLinesTable, fn (Blueprint $table) => $table->index('product_id'));
        }
        if (!$this->hasIndex($orderLinesTable, 'variant_id')) {
            Schema::table($orderLinesTable, fn (Blueprint $table) => $table->index('variant_id'));
        }
        if (!$this->hasIndex($orderLinesTable, ['store_id', 'order_id'])) {
            Schema::table($orderLinesTable, fn (Blueprint $table) => $table->index(['store_id', 'order_id']));
        }

        // Add composite indexes for common Filament queries
        if (!$this->hasIndex($ordersTable, ['store_id', 'financial_status'])) {
            Schema::table($ordersTable, fn (Blueprint $table) => $table->index(['store_id', 'financial_status']));
        }
        if (!$this->hasIndex($ordersTable, ['store_id', 'fulfillment_status'])) {
            Schema::table($ordersTable, fn (Blueprint $table) => $table->index(['store_id', 'fulfillment_status']));
        }
        if (!$this->hasIndex($ordersTable, ['store_id', 'processed_at'])) {
            Schema::table($ordersTable, fn (Blueprint $table) => $table->index(['store_id', 'processed_at']));
        }

        if (!$this->hasIndex($customersTable, ['store_id', 'email'])) {
            Schema::table($customersTable, fn (Blueprint $table) => $table->index(['store_id', 'email']));
        }

        // Add status column to sync_runs if not exists
        if (!Schema::hasColumn($syncRunsTable, 'status')) {
            Schema::table($syncRunsTable, function (Blueprint $table) {
                $table->string('status')->default('completed')->after('entity');
            });
            // Add index separately to avoid SQLite issues
            Schema::table($syncRunsTable, fn (Blueprint $table) => $table->index('status'));
        }
    }

    protected function hasIndex(string $table, string|array $columns): bool
    {
        $columns = (array) $columns;
        $indexName = $table . '_' . implode('_', $columns) . '_index';

        try {
            $indexes = Schema::getIndexes($table);
            foreach ($indexes as $index) {
                if ($index['name'] === $indexName) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // If we can't check indexes, assume it doesn't exist
            return false;
        }

        return false;
    }

    public function down(): void
    {
        // Note: down() is intentionally minimal to avoid SQLite compatibility issues
        // In production with MySQL/PostgreSQL, you may want to drop these indexes manually
    }
};
