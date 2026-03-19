<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('shopify.tables.inventory_levels', 'shopify_inventory_levels'), function (Blueprint $table) {
            $table->integer('on_hand')->nullable()->after('available');
        });
    }

    public function down(): void
    {
        Schema::table(config('shopify.tables.inventory_levels', 'shopify_inventory_levels'), function (Blueprint $table) {
            $table->dropColumn('on_hand');
        });
    }
};
