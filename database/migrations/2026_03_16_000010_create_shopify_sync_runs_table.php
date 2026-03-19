<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('shopify.tables.sync_runs', 'shopify_sync_runs'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained(config('shopify.tables.stores', 'shopify_stores'))->onDelete('cascade');
            $table->string('entity')->index();
            $table->json('params')->nullable();
            $table->json('counts')->nullable();
            $table->json('errors')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('finished_at')->nullable()->index();
            $table->timestamps();

            $table->index(['store_id', 'entity']);
            $table->index(['store_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('shopify.tables.sync_runs', 'shopify_sync_runs'));
    }
};
