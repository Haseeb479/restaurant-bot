<?php

namespace App\Rules;

use App\Support\WebhookUrlValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects outbound webhook URLs that would let an owner make the server issue
 * requests to internal addresses. See App\Support\WebhookUrlValidator.
 */
class SafeWebhookUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail('The :attribute must be a valid URL.');

            return;
        }

        $reason = WebhookUrlValidator::validate($value);

        if ($reason !== null) {
            $fail($reason);
        }
    }
}
