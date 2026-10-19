<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class CategoryIconController extends Controller
{
    public function __invoke(string $filename)
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists('category-icons/'.$filename), 404);

        return response()->file($disk->path('category-icons/'.$filename), [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
