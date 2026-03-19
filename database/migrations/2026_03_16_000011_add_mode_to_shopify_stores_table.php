<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('shopify.tables.stores', 'shopify_stores'), function (Blueprint $table) {
            $table->string('mode')->default('oauth')->after('access_token')->index();
            $table->json('metadata')->nullable()->after('scopes');
        });
    }

    public function down(): void
    {
        // Note: down() is intentionally minimal to avoid SQLite compatibility issues
        // In production with MySQL/PostgreSQL, you may want to drop these columns manually
    }
};
