<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('shopify.tables.customers', 'shopify_customers'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained(config('shopify.tables.stores', 'shopify_stores'))->onDelete('cascade');
            $table->string('shopify_id')->index();
            $table->string('email')->nullable()->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('state')->nullable()->index();
            $table->json('payload');
            $table->timestamp('shopify_created_at')->nullable();
            $table->timestamp('shopify_updated_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_id']);
            $table->index(['store_id', 'email']);
            $table->index(['store_id', 'state']);
            $table->index(['store_id', 'shopify_updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('shopify.tables.customers', 'shopify_customers'));
    }
};
