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

    public static function philhealthNo(mixed $value, string $fallback = 'N/A'): string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return $fallback;
        }

        $digits = preg_replace('/\D/', '', $value);

        if (strlen($digits) !== 12) {
            return $value;
        }

        return substr($digits, 0, 2).'-'.substr($digits, 2, 9).'-'.substr($digits, 11, 1);
    }

    public static function pagibigIdNo(mixed $value, string $fallback = 'N/A'): string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return $fallback;
        }

        $digits = preg_replace('/\D/', '', $value);

        if (strlen($digits) !== 12) {
            return $value;
        }

        return substr($digits, 0, 4).'-'.substr($digits, 4, 4).'-'.substr($digits, 8, 4);
    }

    public static function umidNo(mixed $value, string $fallback = 'N/A'): string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return $fallback;
        }

        $digits = preg_replace('/\D/', '', $value);

        if (strlen($digits) !== 12) {
            return $value;
        }

        return substr($digits, 0, 4).'-'.substr($digits, 4, 7).'-'.substr($digits, 11, 1);
    }

    public static function sssNo(mixed $value, string $fallback = 'N/A'): string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return $fallback;
        }

        $digits = preg_replace('/\D/', '', $value);

        if (strlen($digits) !== 10) {
            return $value;
        }

        return substr($digits, 0, 2).'-'.substr($digits, 2, 7).'-'.substr($digits, 9, 1);
    }

    public static function philsysNo(mixed $value, string $fallback = 'N/A'): string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return $fallback;
        }

        $digits = preg_replace('/\D/', '', $value);

        if (strlen($digits) !== 16) {
            return $value;
        }

        return substr($digits, 0, 4).'-'.substr($digits, 4, 4).'-'.substr($digits, 8, 4).'-'.substr($digits, 12, 4);
    }

    public static function tinNo(mixed $value, string $fallback = 'N/A'): string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return $fallback;
        }

        $digits = preg_replace('/\D/', '', $value);

        if (strlen($digits) !== 12) {
            return $value;
        }

        return substr($digits, 0, 3).'-'.substr($digits, 3, 3).'-'.substr($digits, 6, 3).'-'.substr($digits, 9, 3);
    }

    public static function check(?string $value, string $expected): string
    {
        return strcasecmp((string) $value, $expected) === 0 ? '☑' : '☐';
    }
}
