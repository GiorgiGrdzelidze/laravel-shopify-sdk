<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('shopify.tables.logs', 'shopify_logs'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained(config('shopify.tables.stores', 'shopify_stores'))->nullOnDelete();
            $table->string('action');
            $table->string('entity_type');
            $table->string('entity_id')->nullable();
            $table->string('status')->default('success');
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['action', 'status']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('shopify.tables.logs', 'shopify_logs'));
    }
};
