<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('shopify.tables.logs', 'shopify_logs'), function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('store_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table(config('shopify.tables.logs', 'shopify_logs'), function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
