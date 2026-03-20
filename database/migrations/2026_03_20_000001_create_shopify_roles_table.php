<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('shopify_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('group')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('shopify_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('shopify_roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('shopify_permissions')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('shopify_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('shopify_roles')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });

        Schema::create('shopify_user_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('store_id')->constrained('shopify_stores')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_user_stores');
        Schema::dropIfExists('shopify_user_roles');
        Schema::dropIfExists('shopify_role_permissions');
        Schema::dropIfExists('shopify_permissions');
        Schema::dropIfExists('shopify_roles');
    }
};
