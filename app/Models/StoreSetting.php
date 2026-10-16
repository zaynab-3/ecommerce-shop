<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StoreSetting extends Model
{
    protected $guarded = [];

    protected $casts = ['delivery_enabled' => 'boolean', 'whatsapp_enabled' => 'boolean'];

    public static function current(): self
    {
        return Cache::remember('store.settings', 300, fn () => static::firstOrCreate(['id' => 1]));
    }
}
