<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('shopify.tables.stores', 'shopify_stores'), function (Blueprint $table) {
            $table->string('custom_domain')->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        // Intentionally minimal for SQLite compatibility
    }
};
