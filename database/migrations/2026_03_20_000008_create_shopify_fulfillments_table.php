<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fulfillment Orders (what needs to be fulfilled)
        Schema::create('shopify_fulfillment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('shopify_stores')->cascadeOnDelete();
            $table->string('shopify_id')->index();
            $table->foreignId('order_id')->nullable()->constrained('shopify_orders')->nullOnDelete();
            $table->string('order_shopify_id')->nullable()->index();
            $table->string('status'); // open, in_progress, cancelled, incomplete, closed
            $table->string('request_status'); // unsubmitted, submitted, accepted, rejected, cancellation_requested
            $table->foreignId('location_id')->nullable()->constrained('shopify_locations')->nullOnDelete();
            $table->json('line_items')->nullable();
            $table->json('destination')->nullable();
            $table->json('delivery_method')->nullable();
            $table->timestamp('fulfill_at')->nullable();
            $table->timestamp('fulfill_by')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_id']);
        });

        // Fulfillments (actual shipments)
        Schema::create('shopify_fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('shopify_stores')->cascadeOnDelete();
            $table->string('shopify_id')->index();
            $table->foreignId('order_id')->nullable()->constrained('shopify_orders')->nullOnDelete();
            $table->string('order_shopify_id')->nullable()->index();
            $table->string('status'); // pending, open, success, cancelled, error, failure
            $table->string('name')->nullable(); // #1001-F1
            $table->string('tracking_company')->nullable();
            $table->string('tracking_number')->nullable();
            $table->json('tracking_numbers')->nullable();
            $table->json('tracking_urls')->nullable();
            $table->json('line_items')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('shopify_locations')->nullOnDelete();
            $table->string('shipment_status')->nullable(); // label_printed, label_purchased, attempted_delivery, etc.
            $table->timestamp('created_at_shopify')->nullable();
            $table->timestamp('updated_at_shopify')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_id']);
        });

        // Fulfillment Events (tracking updates)
        Schema::create('shopify_fulfillment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fulfillment_id')->constrained('shopify_fulfillments')->cascadeOnDelete();
            $table->string('shopify_id')->nullable()->index();
            $table->string('status'); // in_transit, out_for_delivery, delivered, etc.
            $table->string('message')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('country')->nullable();
            $table->string('zip')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('happened_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_fulfillment_events');
        Schema::dropIfExists('shopify_fulfillments');
        Schema::dropIfExists('shopify_fulfillment_orders');
    }
};
