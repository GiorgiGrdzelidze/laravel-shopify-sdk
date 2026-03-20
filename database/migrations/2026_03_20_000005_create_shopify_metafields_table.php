<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Metafield Definitions (schemas)
        Schema::create('shopify_metafield_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('shopify_stores')->cascadeOnDelete();
            $table->string('shopify_id')->nullable()->index();
            $table->string('namespace');
            $table->string('key');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // single_line_text_field, multi_line_text_field, number_integer, etc.
            $table->string('owner_type'); // PRODUCT, COLLECTION, CUSTOMER, ORDER, VARIANT
            $table->json('validations')->nullable();
            $table->boolean('pinned')->default(false);
            $table->timestamps();

            $table->unique(['store_id', 'namespace', 'key', 'owner_type'], 'metafield_def_unique');
        });

        // Metafields (actual values)
        Schema::create('shopify_metafields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('shopify_stores')->cascadeOnDelete();
            $table->string('shopify_id')->nullable()->index();
            $table->string('namespace');
            $table->string('key');
            $table->text('value');
            $table->string('type'); // single_line_text_field, json, number_integer, etc.
            $table->string('owner_type'); // PRODUCT, COLLECTION, CUSTOMER, ORDER, VARIANT
            $table->string('owner_id'); // Shopify GID of the owner
            $table->foreignId('definition_id')->nullable()->constrained('shopify_metafield_definitions')->nullOnDelete();
            $table->timestamps();

            $table->unique(['store_id', 'namespace', 'key', 'owner_id'], 'metafield_unique');
            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_metafields');
        Schema::dropIfExists('shopify_metafield_definitions');
    }
};
