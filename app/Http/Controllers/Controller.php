<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ActiveBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

abstract class Controller
{
    protected function activeBusinessUsers(): Builder
    {
        $userIds = $this->activeBusinessUserIds();

        return User::query()
            ->where('role', '!=', 'client_portal')
            ->when(
                $userIds,
                fn (Builder $query) => $query->whereIn('id', $userIds),
                fn (Builder $query) => $query->whereRaw('1 = 0')
            );
    }

    protected function activeBusinessUserIds(): array
    {
        if (! Schema::hasTable('business_user') || ! ActiveBusiness::id()) {
            return [];
        }

        return DB::table('business_user')
            ->where('business_id', ActiveBusiness::id())
            ->whereIn('status', ['Active', 'Pending Invitation'])
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function activeBusinessUserExistsRule(string $column = 'id'): Exists
    {
        return Rule::exists('users', $column)
            ->where(fn ($query) => $query->whereIn('id', $this->activeBusinessUserIds()));
    }

    protected function abortUnlessActiveBusinessUser(User $user): void
    {
        abort_unless(in_array((int) $user->id, $this->activeBusinessUserIds(), true), 404);
    }

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
