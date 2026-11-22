<?php

namespace App\Support;

class NotificationTitle
{
    public static function format(?string $title): string
    {
        $title = trim($title ?? '') ?: 'Notification';
        $title = match ($title) {
            'cto_fully_approved' => 'CTO request approved',
            'cto_hr_approved' => 'CTO request ready for chief approval',
            'cto_chief_approved' => 'CTO request ready for final approval',
            'cto_rejected' => 'CTO request rejected',
            default => str_replace('_', ' ', $title),
        };
        $title = ucfirst(mb_strtolower(trim(str_replace(['✅', '❌'], '', $title))));
        return preg_replace_callback('/\b(cto|coc|ta|tar|hr|pds|saln|qr|dmw|otp)\b/i', fn ($match) => strtoupper($match[0]), $title);
    }
}
