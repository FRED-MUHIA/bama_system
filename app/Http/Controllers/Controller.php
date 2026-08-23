<?php

namespace App\Http\Controllers;

use Illuminate\Validation\ValidationException;

abstract class Controller
{
    protected function validatedEmailList(?string $value, string $field = 'cc'): array
    {
        if (blank($value)) {
            return [];
        }

        $emails = array_values(array_unique(array_map(
            fn ($email) => trim($email),
            preg_split('/[\s,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY)
        )));

        $invalid = array_values(array_filter($emails, fn ($email) => ! filter_var($email, FILTER_VALIDATE_EMAIL)));

        if ($invalid) {
            throw ValidationException::withMessages([
                $field => 'Enter valid email addresses separated by commas or semicolons.',
            ]);
        }

        return $emails;
    }
}
