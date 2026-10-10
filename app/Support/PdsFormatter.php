<?php

namespace App\Support;

use Carbon\Carbon;

class PdsFormatter
{
    public static function date(mixed $value, string $fallback = ''): string
    {
        if (blank($value)) {
            return $fallback;
        }

        try {
            return Carbon::parse($value)->format('m/d/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    public static function val(mixed $value, string $fallback = 'N/A'): string
    {
        return filled($value) ? (string) $value : $fallback;
    }

    public static function heightCm(mixed $value, string $fallback = 'N/A'): string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return $fallback;
        }

        $numeric = preg_replace('/[^0-9.]/', '', $value);

        if ($numeric === '' || ! is_numeric($numeric)) {
            return $value;
        }

        $height = (float) $numeric;

        if ($height > 0 && $height < 3) {
            $height *= 100;
        }

        return rtrim(rtrim(number_format($height, 2, '.', ''), '0'), '.');
    }

    public static function check(?string $value, string $expected): string
    {
        return strcasecmp((string) $value, $expected) === 0 ? '☑' : '☐';
    }
}
