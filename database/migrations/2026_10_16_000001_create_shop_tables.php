<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('orders');
            $t->boolean('is_active')->default(true);
        });
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('icon')->default('grid');
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('position')->default(0);
            $t->timestamps();
            $t->index(['parent_id', 'is_active', 'position']);
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('sku')->unique();
            $t->text('description');
            $t->unsignedInteger('price_cents');
            $t->unsignedInteger('compare_price_cents')->nullable();
            $t->boolean('is_active')->default(true);
            $t->boolean('is_featured')->default(false);
            $t->string('stock_mode')->default('tracked');
            $t->unsignedInteger('quantity')->nullable();
            $t->unsignedInteger('low_stock_threshold')->default(5);
            $t->string('manual_stock_status')->default('available');
            $t->unsignedInteger('position')->default(0);
            $t->string('badge')->nullable();
            $t->timestamps();
            $t->index(['is_active', 'position']);
            $t->index(['category_id', 'is_active']);
        });
        Schema::create('product_colors', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('hex', 7)->nullable();
            $t->unsignedInteger('position')->default(0);
            $t->timestamps();
        });
        Schema::create('product_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_color_id')->nullable()->constrained('product_colors')->nullOnDelete();
            $t->string('path');
            $t->string('alt');
            $t->unsignedInteger('position')->default(0);
            $t->timestamps();
        });
        Schema::create('delivery_zones', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('governorate');
            $t->unsignedInteger('fee_cents')->default(0);
            $t->boolean('is_active')->default(false);
            $t->timestamps();
            $t->index(['is_active', 'governorate']);
        });
        Schema::create('store_settings', function (Blueprint $t) {
            $t->id();
            $t->string('store_name')->default('MESH');
            $t->boolean('delivery_enabled')->default(true);
            $t->boolean('whatsapp_enabled')->default(false);
            $t->string('whatsapp_recipient')->nullable();
            $t->timestamps();
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->uuid('idempotency_key')->unique();
            $t->string('number')->unique();
            $t->string('customer_name');
            $t->string('phone');
            $t->string('email')->nullable();
            $t->text('address');
            $t->text('notes')->nullable();
            $t->foreignId('delivery_zone_id')->nullable()->constrained()->nullOnDelete();
            $t->string('delivery_area')->nullable();
            $t->unsignedBigInteger('subtotal_cents');
            $t->unsignedInteger('delivery_cents');
            $t->unsignedBigInteger('total_cents');
            $t->string('status')->default('pending');
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('stock_restored_at')->nullable();
            $t->timestamp('whatsapp_sent_at')->nullable();
            $t->text('whatsapp_error')->nullable();
            $t->timestamps();
            $t->index(['status', 'created_at']);
            $t->index(['assigned_to', 'status']);
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('sku');
            $t->string('color')->nullable();
            $t->boolean('tracked_stock');
            $t->unsignedInteger('quantity');
            $t->unsignedInteger('price_cents');
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action');
            $t->string('subject');
            $t->json('metadata')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'order_items', 'orders', 'store_settings', 'delivery_zones', 'product_images', 'product_colors', 'products', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'is_active']));
    }
};
