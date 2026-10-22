<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->string('phone')->nullable()->change());
    }

    public function down(): void
    {
        DB::table('orders')->whereNull('phone')->update(['phone' => '']);
        Schema::table('orders', fn (Blueprint $table) => $table->string('phone')->nullable(false)->change());
    }
};
