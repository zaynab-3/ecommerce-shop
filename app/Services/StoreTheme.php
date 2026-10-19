<?php

namespace App\Services;

class StoreTheme
{
    public const DEFAULT_COLOR = '#3868e9';

    public const VERSION = 2;

    public static function normalize(?string $color): string
    {
        return preg_match('/^#[0-9a-f]{6}$/iD', $color ?? '') ? strtolower($color) : self::DEFAULT_COLOR;
    }

    /** The interface uses only the saved accent, black and white. */
    public static function variables(string $color): array
    {
        $color = self::normalize($color);
        $channels = array_map(fn ($c) => hexdec($c) / 255, str_split(substr($color, 1), 2));
        $linear = array_map(fn ($c) => $c <= .04045 ? $c / 12.92 : (($c + .055) / 1.055) ** 2.4, $channels);
        $luminance = $linear[0] * .2126 + $linear[1] * .7152 + $linear[2] * .0722;
        $whiteContrast = 1.05 / ($luminance + .05) >= 4.5;

        return [
            '--store-accent' => $color,
            '--store-on-accent' => $whiteContrast ? '#ffffff' : '#000000',
            '--store-accent-hover' => $color,
            '--store-accent-ink' => $whiteContrast ? $color : '#000000',
            '--store-text' => '#000000',
            '--store-muted' => '#000000',
            '--store-tint' => '#ffffff',
            '--store-surface' => '#ffffff',
            '--store-border' => 'transparent',
        ];
    }
}
