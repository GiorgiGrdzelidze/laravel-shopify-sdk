<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopify_products', function (Blueprint $table) {
            if (!Schema::hasColumn('shopify_products', 'images')) {
                $table->json('images')->nullable()->after('product_type');
            }
            if (!Schema::hasColumn('shopify_products', 'featured_image_url')) {
                $table->string('featured_image_url', 2048)->nullable()->after('images');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shopify_products', function (Blueprint $table) {
            $table->dropColumn(['images', 'featured_image_url']);
        });
    }
};
