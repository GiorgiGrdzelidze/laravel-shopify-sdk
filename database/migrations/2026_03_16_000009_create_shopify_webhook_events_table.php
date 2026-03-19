<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('shopify.tables.webhook_events', 'shopify_webhook_events'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained(config('shopify.tables.stores', 'shopify_stores'))->onDelete('cascade');
            $table->string('shop_domain')->index();
            $table->string('topic')->index();
            $table->string('webhook_id')->nullable()->index();
            $table->json('payload');
            $table->string('status')->default('pending')->index();
            $table->text('error')->nullable();
            $table->timestamp('received_at')->nullable()->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['store_id', 'topic']);
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'topic', 'webhook_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('shopify.tables.webhook_events', 'shopify_webhook_events'));
    }
};
