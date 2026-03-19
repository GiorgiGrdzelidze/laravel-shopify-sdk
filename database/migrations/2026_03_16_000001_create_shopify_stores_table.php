<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('shopify.tables.stores', 'shopify_stores'), function (Blueprint $table) {
            $table->id();
            $table->string('shop_domain')->unique();
            $table->text('access_token');
            $table->string('scopes')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('uninstalled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('shopify.tables.stores', 'shopify_stores'));
    }
};
