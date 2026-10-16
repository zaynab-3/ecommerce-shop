<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImageService
{
    public function store(UploadedFile $file): string
    {
        $info = @getimagesize($file->getRealPath());
        if (! $info || $info[0] * $info[1] > 40000000 || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw ValidationException::withMessages(['images' => 'Use JPEG, PNG or WebP images under 40 megapixels.']);
        }
        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file->getRealPath()),IMAGETYPE_PNG => @imagecreatefrompng($file->getRealPath()),IMAGETYPE_WEBP => @imagecreatefromwebp($file->getRealPath())
        };
        if (! $image) {
            throw ValidationException::withMessages(['images' => 'This image could not be decoded.']);
        }
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $orientation = @exif_read_data($file->getRealPath())['Orientation'] ?? 1;
            if (in_array($orientation, [2, 4, 5, 7])) {
                imageflip($image, IMG_FLIP_HORIZONTAL);
            }$degrees = match ($orientation) {
                3,4 => 180,5,6 => -90,7,8 => 90,default => 0
            };
            if ($degrees) {
                $rotated = imagerotate($image, $degrees, 0);
                imagedestroy($image);
                $image = $rotated;
            }
        }
        $w = imagesx($image);
        $h = imagesy($image);
        $scale = min(1, 1600 / max($w, $h));
        $output = imagecreatetruecolor(max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale)));
        imagealphablending($output, false);
        imagesavealpha($output, true);
        imagecopyresampled($output, $image, 0, 0, 0, 0, imagesx($output), imagesy($output), $w, $h);
        ob_start();
        imagewebp($output, null, 84);
        $bytes = ob_get_clean();
        imagedestroy($image);
        if (! $bytes) {
            throw ValidationException::withMessages(['images' => 'Image conversion failed.']);
        }
        $path = 'products/'.Str::uuid().'.webp';
        $thumb = imagescale($output, min(480, imagesx($output)));
        ob_start();
        imagewebp($thumb, null, 82);
        $thumbBytes = ob_get_clean();
        imagedestroy($thumb);
        imagedestroy($output);
        if (! $thumbBytes) {
            throw ValidationException::withMessages(['images' => 'Thumbnail conversion failed.']);
        }
        try {
            if (! Storage::disk('public')->put($path, $bytes) || ! Storage::disk('public')->put(str_replace('.webp', '-480.webp', $path), $thumbBytes)) {
                throw new \RuntimeException('Unable to save product images. Check storage permissions.');
            }
        } catch (\Throwable $e) {
            Storage::disk('public')->delete([$path, str_replace('.webp', '-480.webp', $path)]);
            throw $e;
        }

        return $path;
    }
}
