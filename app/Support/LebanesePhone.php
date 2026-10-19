<?php

namespace App\Support;

class LebanesePhone
{
    public static function normalize(string $value): ?string
    {
        if (! preg_match('/^\+?[0-9 ()-]+$/', trim($value))) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $value);
        if (str_starts_with(trim($value), '+') && ! str_starts_with($digits, '961')) {
            return null;
        }
        if (str_starts_with($digits, '00961')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '961')) {
            $digits = substr($digits, 3);
        }
        $digits = preg_replace('/^0/', '', $digits);
        // Lebanese landlines and 03 mobiles have seven international digits;
        // the newer mobile ranges have eight. Never store a trunk zero after +961.
        if (! preg_match('/^(?:[1-9][0-9]{6}|(?:70|71|76|78|79|81)[0-9]{6})$/', $digits)) {
            return null;
        }

        return '+961'.$digits;
    }
}
