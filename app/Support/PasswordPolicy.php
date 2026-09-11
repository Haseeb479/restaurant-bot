<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class PasswordPolicy
{
    /**
     * Get the standardized 12+ character password rule across Foodio SaaS (Req 10).
     */
    public static function rule(bool $required = true): array
    {
        $rule = Password::min(12);

        return [
            $required ? 'required' : 'nullable',
            'string',
            $rule,
        ];
    }
}
