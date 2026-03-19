<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('shopify.tables.customers', 'shopify_customers'), function (Blueprint $table) {
            $table->string('phone')->nullable()->after('last_name');
            $table->unsignedInteger('orders_count')->default(0)->after('state');
            $table->decimal('total_spent', 12, 2)->default(0)->after('orders_count');
        });
    }

    public function down(): void
    {
        Schema::table(config('shopify.tables.customers', 'shopify_customers'), function (Blueprint $table) {
            $table->dropColumn(['phone', 'orders_count', 'total_spent']);
        });
    }
};
