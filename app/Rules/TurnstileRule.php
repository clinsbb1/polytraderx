<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\Security\TurnstileService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TurnstileRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $verified = app(TurnstileService::class)->verify(
            is_string($value) ? $value : null,
            request()->ip()
        );

        if (!$verified) {
            $fail('Turnstile verification failed. Please try again.');
        }
    }
}
