<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class PasswordPolicy
{
    /**
     * Get the standardized 6+ character password rule across Foodio SaaS.
     */
    public static function rule(bool $required = true): array
    {
        $rule = Password::min(6);

        return [
            $required ? 'required' : 'nullable',
            'string',
            $rule,
        ];
    }
}
