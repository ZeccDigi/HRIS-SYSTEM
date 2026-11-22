<?php

namespace App\Support;

class ApproverRemark
{
    public static function visible(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || preg_match('/\[DEMO[^\]]*\]|simulated (verification|recommendation|final approval)|no actual officer action/i', $value)) return null;
        return $value;
    }
}
