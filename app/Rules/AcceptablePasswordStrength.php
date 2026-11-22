<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AcceptablePasswordStrength implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) { $fail('Use a Medium or Strong password.'); return; }
        $categories = 0;
        foreach (['/\p{Lu}/u', '/\p{Ll}/u', '/\p{N}/u', '/[\p{Z}\p{S}\p{P}]/u'] as $pattern) {
            if (preg_match($pattern, $value)) $categories++;
        }
        if (mb_strlen($value) < 8 || $categories < 2) {
            $fail('Use a Medium or Strong password with at least 8 characters and a mix of character types.');
        }
    }
}
