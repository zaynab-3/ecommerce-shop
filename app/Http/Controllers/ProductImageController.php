<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductImageController extends Controller
{
    public function __invoke(string $filename): BinaryFileResponse
    {
        $disk = Storage::disk('public');
        $directory = realpath($disk->path('products'));
        $file = realpath($disk->path('products/'.$filename));
        abort_unless($directory && $file && is_file($file)
            && str_starts_with($file, $directory.DIRECTORY_SEPARATOR), 404);

        return response()->file($file, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
