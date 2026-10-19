<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', fn (Blueprint $table) => $table->string('accent_color', 7)->default('#3868e9'));
    }

    public function down(): void
    {
        Schema::table('store_settings', fn (Blueprint $table) => $table->dropColumn('accent_color'));
    }
};
