<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_draft_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('shopify_stores')->cascadeOnDelete();
            $table->string('shopify_id')->nullable()->index();
            $table->string('name')->nullable(); // #D1, #D2, etc.
            $table->string('status')->default('open'); // open, invoice_sent, completed
            $table->foreignId('customer_id')->nullable()->constrained('shopify_customers')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->json('line_items')->nullable();
            $table->json('applied_discount')->nullable();
            $table->string('shipping_line')->nullable();
            $table->decimal('subtotal_price', 12, 2)->default(0);
            $table->decimal('total_tax', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->text('note')->nullable();
            $table->json('note_attributes')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('tax_exempt')->default(false);
            $table->boolean('taxes_included')->default(false);
            $table->string('invoice_url')->nullable();
            $table->timestamp('invoice_sent_at')->nullable();
            $table->string('order_id')->nullable(); // Shopify order ID when completed
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_draft_orders');
    }
};
