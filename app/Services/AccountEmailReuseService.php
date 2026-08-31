<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountEmailReuseService
{
    public const SELF_DELETION_HOLD_MONTHS = 4;

    public function assertEmailCanRegister(string $email): void
    {
        $email = $this->normalize($email);

        if ($releaseAt = $this->activeSelfDeletionReleaseAt($email)) {
            throw ValidationException::withMessages([
                'email' => 'This email was used on a self-deleted account. You can create a new account with it from '.$releaseAt->toFormattedDateString().'.',
            ]);
        }

        $blockingUser = User::where('email', $email)
            ->get()
            ->first(fn (User $user) => ! $this->canReleaseUserEmail($user));

        if ($blockingUser) {
            throw ValidationException::withMessages([
                'email' => 'That email is already registered.',
            ]);
        }
    }

    public function releaseEmailForRegistration(string $email): void
    {
        $email = $this->normalize($email);
        $this->assertEmailCanRegister($email);

        User::where('email', $email)
            ->get()
            ->each(fn (User $user) => $this->anonymizeUserEmail($user, 'released'));
    }

    public function releaseTenantUsersImmediately(iterable $userIds): void
    {
        User::whereIn('id', collect($userIds)->filter()->unique()->values())
            ->get()
            ->each(function (User $user) {
                if ($this->canReleaseUserEmail($user)) {
                    $this->anonymizeUserEmail($user, 'super-admin-deleted');
                }
            });
    }

    public function holdSelfDeletedAccount(User $user): Carbon
    {
        $email = $this->normalize($user->email);
        $releaseAt = now()->addMonthsNoOverflow(self::SELF_DELETION_HOLD_MONTHS);

        if (Schema::hasTable('account_email_reuse_holds')) {
            DB::table('account_email_reuse_holds')->updateOrInsert(
                ['email_hash' => $this->hash($email)],
                [
                    'user_id' => $user->id,
                    'reason' => 'self_deleted',
                    'release_at' => $releaseAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->removeAccountAccess($user);
        $this->anonymizeUserEmail($user, 'self-deleted');

        return $releaseAt;
    }

    private function activeSelfDeletionReleaseAt(string $email): ?Carbon
    {
        if (! Schema::hasTable('account_email_reuse_holds')) {
            return null;
        }

        $releaseAt = DB::table('account_email_reuse_holds')
            ->where('email_hash', $this->hash($email))
            ->where('reason', 'self_deleted')
            ->where('release_at', '>', now())
            ->value('release_at');

        return $releaseAt ? Carbon::parse($releaseAt) : null;
    }

    private function canReleaseUserEmail(User $user): bool
    {
        if ($user->role === 'super_admin') {
            return false;
        }

        if (Schema::hasColumn('users', 'current_tenant_id') && $user->current_tenant_id) {
            return false;
        }

        return ! $this->userHasProfileAccess($user);
    }

    private function userHasProfileAccess(User $user): bool
    {
        return (Schema::hasTable('tenant_user') && DB::table('tenant_user')->where('user_id', $user->id)->exists())
            || (Schema::hasTable('business_user') && DB::table('business_user')->where('user_id', $user->id)->exists());
    }

    private function removeAccountAccess(User $user): void
    {
        foreach (['sessions', 'otp_codes', 'login_tokens'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->where('user_id', $user->id)->delete();
            }
        }

        if (Schema::hasTable('password_reset_tokens')) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        }

        foreach (['tenant_user', 'business_user', 'team_user', 'iam_permission_user'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->where('user_id', $user->id)->delete();
            }
        }
    }

    private function anonymizeUserEmail(User $user, string $context): void
    {
        $token = Str::lower(Str::random(10));
        $email = "deleted-user-{$user->id}-{$token}@deleted.local";

        while (User::where('email', $email)->whereKeyNot($user->id)->exists()) {
            $token = Str::lower(Str::random(10));
            $email = "deleted-user-{$user->id}-{$token}@deleted.local";
        }

        $updates = [
            'name' => $user->name ?: 'Deleted User',
            'email' => $email,
            'password' => Hash::make(Str::random(64)),
            'remember_token' => null,
            'updated_at' => now(),
        ];

        foreach ([
            'current_tenant_id' => null,
            'is_active' => false,
            'status' => 'Archived',
            'session_version' => (int) ($user->session_version ?? 0) + 1,
            'enable_password_login' => false,
            'enable_otp_login' => false,
            'enable_magic_link_login' => false,
        ] as $column => $value) {
            if (Schema::hasColumn('users', $column)) {
                $updates[$column] = $value;
            }
        }

        if (Schema::hasColumn('users', 'username')) {
            $updates['username'] = "deleted.{$context}.{$user->id}.{$token}";
        }

        $user->forceFill($updates)->save();
    }

    private function normalize(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function hash(string $email): string
    {
        return hash('sha256', $this->normalize($email));
    }
}
