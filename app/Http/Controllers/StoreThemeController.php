<?php

namespace App\Http\Controllers;

use App\Services\StoreTheme;

class StoreThemeController extends Controller
{
    public function __invoke(string $color)
    {
        $css = ':root{';
        foreach (StoreTheme::variables('#'.$color) as $name => $value) {
            $css .= $name.':'.$value.';';
        }

        return response($css.'}', 200, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
