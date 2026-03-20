<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make shopify_id nullable to allow local creation before Shopify sync
        \DB::statement('ALTER TABLE shopify_collections MODIFY shopify_id VARCHAR(255) NULL');
    }

    public function down(): void
    {
        \DB::statement('ALTER TABLE shopify_collections MODIFY shopify_id VARCHAR(255) NOT NULL');
    }
};
