<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CatalogRevision
{
    public static function current(): string
    {
        return Cache::rememberForever('catalog.revision', fn () => (string) Str::uuid());
    }

    public static function publish(): void
    {
        Cache::forget('catalog.categories');
        Cache::forever('catalog.revision', (string) Str::uuid());
    }
}
