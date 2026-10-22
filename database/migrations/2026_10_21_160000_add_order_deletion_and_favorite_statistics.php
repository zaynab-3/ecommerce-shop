<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->softDeletes());
        Schema::create('product_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->char('visitor_key', 64);
            $table->boolean('is_saved')->default(true);
            $table->unsignedInteger('save_count')->default(1);
            $table->timestamps();
            $table->unique(['product_id', 'visitor_key']);
            $table->index(['is_saved', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_favorites');
        Schema::table('orders', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
