<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountEmailReuseService
{
    public const SELF_DELETION_HOLD_MONTHS = 4;

    public const SUPER_ADMIN_DELETION_HOLD_MONTHS = 3;

    private const REASON_SELF_DELETED = 'self_deleted';

    private const REASON_SUPER_ADMIN_DELETED = 'super_admin_deleted';

    public function assertEmailCanRegister(string $email): void
    {
        $email = $this->normalize($email);

        if ($hold = $this->activeReuseHold($email)) {
            throw ValidationException::withMessages([
                'email' => $this->registrationHoldMessage($hold->reason, Carbon::parse($hold->release_at)),
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

    public function holdTenantUsersForSuperAdminDeletion(iterable $userIds, ?string $profileName = null): Collection
    {
        return User::whereIn('id', collect($userIds)->filter()->unique()->values())
            ->get()
            ->map(function (User $user) use ($profileName) {
                if ($this->canReleaseUserEmail($user)) {
                    return $this->holdSuperAdminDeletedAccount($user, $profileName);
                }

                return null;
            })
            ->filter()
            ->values();
    }

    public function sendSuperAdminDeletionNotices(iterable $notices): int
    {
        $sent = 0;

        foreach ($notices as $notice) {
            try {
                app(OutgoingMailService::class)->sendRaw(
                    $notice['email'],
                    'Your Bama profile was deleted',
                    $this->superAdminDeletionEmailBody($notice),
                );
                $sent++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $sent;
    }

    public function holdSelfDeletedAccount(User $user): Carbon
    {
        $email = $this->normalize($user->email);
        $releaseAt = now()->addMonthsNoOverflow(self::SELF_DELETION_HOLD_MONTHS);

        $this->recordEmailReuseHold($email, $user, self::REASON_SELF_DELETED, $releaseAt);

        $this->removeAccountAccess($user);
        $this->anonymizeUserEmail($user, 'self-deleted');

        return $releaseAt;
    }

    private function holdSuperAdminDeletedAccount(User $user, ?string $profileName): array
    {
        $email = $this->normalize($user->email);
        $releaseAt = now()->addMonthsNoOverflow(self::SUPER_ADMIN_DELETION_HOLD_MONTHS);
        $notice = [
            'email' => $email,
            'name' => $user->name ?: 'there',
            'profile_name' => $profileName ?: config('app.name'),
            'release_at' => $releaseAt,
        ];

        $this->recordEmailReuseHold($email, $user, self::REASON_SUPER_ADMIN_DELETED, $releaseAt);
        $this->removeAccountAccess($user);
        $this->anonymizeUserEmail($user, 'super-admin-deleted');

        return $notice;
    }

    private function activeReuseHold(string $email): ?object
    {
        if (! Schema::hasTable('account_email_reuse_holds')) {
            return null;
        }

        return DB::table('account_email_reuse_holds')
            ->where('email_hash', $this->hash($email))
            ->where('release_at', '>', now())
            ->first(['reason', 'release_at']);
    }

    private function registrationHoldMessage(string $reason, Carbon $releaseAt): string
    {
        if ($reason === self::REASON_SUPER_ADMIN_DELETED) {
            return 'This email was used on a profile deleted by a super admin. You can create a new account with it from '.$releaseAt->toFormattedDateString().'.';
        }

        return 'This email was used on a self-deleted account. You can create a new account with it from '.$releaseAt->toFormattedDateString().'.';
    }

    private function recordEmailReuseHold(string $email, User $user, string $reason, Carbon $releaseAt): void
    {
        if (! Schema::hasTable('account_email_reuse_holds')) {
            return;
        }

        DB::table('account_email_reuse_holds')->updateOrInsert(
            ['email_hash' => $this->hash($email)],
            [
                'user_id' => $user->id,
                'reason' => $reason,
                'release_at' => $releaseAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function superAdminDeletionEmailBody(array $notice): string
    {
        $releaseAt = $notice['release_at'] instanceof Carbon
            ? $notice['release_at']
            : Carbon::parse($notice['release_at']);

        return implode("\n", [
            'Hello '.$notice['name'].',',
            '',
            'A Bama profile connected to this email was deleted by the platform super admin.',
            'Profile: '.$notice['profile_name'],
            'Account email: '.$notice['email'],
            '',
            'For security, this email is temporarily held before it can be used to create a new account.',
            'You can create a new account with this email from '.$releaseAt->toFormattedDateString().'.',
            '',
            'If you think this was a mistake, contact the profile owner or Bama support.',
            '',
            'Bama secure workspace access',
        ]);
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
