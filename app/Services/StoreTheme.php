<?php

namespace App\Services;

class StoreTheme
{
    public const DEFAULT_COLOR = '#3868e9';

    public static function normalize(?string $color): string
    {
        return preg_match('/^#[0-9a-f]{6}$/iD', $color ?? '') ? strtolower($color) : self::DEFAULT_COLOR;
    }

    /** Keep button labels and prices readable, including with very light brand colors. */
    public static function variables(string $color): array
    {
        $color = self::normalize($color);
        $rgb = array_map(hexdec(...), str_split(substr($color, 1), 2));
        $mix = fn (float $white) => '#'.implode('', array_map(fn ($c) => sprintf('%02x', (int) round($c * (1 - $white) + 255 * $white)), $rgb));
        $darken = fn (float $factor) => array_map(fn ($c) => (int) round($c * $factor), $rgb);
        $luminance = static function (array $channels): float {
            $linear = array_map(fn ($c) => $c / 255 <= .04045 ? $c / 255 / 12.92 : (($c / 255 + .055) / 1.055) ** 2.4, $channels);

            return $linear[0] * .2126 + $linear[1] * .7152 + $linear[2] * .0722;
        };
        $hex = fn ($channels) => '#'.implode('', array_map(fn ($c) => sprintf('%02x', $c), $channels));
        $ink = $rgb;
        for ($factor = .95; 1.05 / ($luminance($ink) + .05) < 4.5; $factor -= .05) {
            $ink = $darken(max(0, $factor));
        }

        return [
            '--store-accent' => $color,
            '--store-on-accent' => 1.05 / ($luminance($rgb) + .05) >= 4.5 ? '#ffffff' : '#000000',
            '--store-accent-hover' => $hex($darken(.9)),
            '--store-accent-ink' => $hex($ink),
            '--store-tint' => $mix(.91),
            '--store-surface' => $mix(.96),
            '--store-border' => $mix(.87),
        ];
    }
}
