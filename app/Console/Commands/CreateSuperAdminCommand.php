<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\IamRole;
use App\Models\Tenant;
use App\Models\User;
use App\Services\IamService;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateSuperAdminCommand extends Command
{
    protected $signature = 'super-admin:create
        {email=superadmin@bama.co.ke : Super admin email address}
        {--name=Bama Super Admin : Display name}
        {--username=superadmin : Login username}
        {--password= : Password to set}
        {--generate-password : Generate and print a secure temporary password}
        {--with-business-access : Attach the account to existing tenants and businesses for emergency support}';

    protected $description = 'Create or update a platform super admin account.';

    public function handle(IamService $iam): int
    {
        $email = Str::lower(trim($this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->components->error('Please provide a valid email address.');

            return self::FAILURE;
        }

        if (! $this->usersTableIsReady()) {
            return self::FAILURE;
        }

        $password = $this->password();

        if (strlen($password) < 12) {
            $this->components->error('The super admin password must be at least 12 characters.');

            return self::FAILURE;
        }

        try {
            $user = DB::transaction(function () use ($email, $password, $iam) {
                $user = User::updateOrCreate(
                    ['email' => $email],
                    $this->userAttributes($password)
                );

                $user->forceFill(['email_verified_at' => now()])->save();

                if ($this->option('with-business-access')) {
                    $this->attachToExistingWorkspaces($user, $iam);
                } else {
                    $this->detachFromWorkspaces($user);
                }

                if (Schema::hasColumn('users', 'current_tenant_id') && ! $this->option('with-business-access')) {
                    $user->forceFill(['current_tenant_id' => null])->saveQuietly();
                }

                return $user;
            });
        } catch (\Throwable $e) {
            report($e);
            $this->components->error('Could not create the super admin account: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Super admin account is ready.');
        $this->line('Email: '.$user->email);
        $this->line('Username: '.$user->username);
        $this->line('Role: '.$user->role);

        if ($this->option('generate-password')) {
            $this->newLine();
            $this->warn('Temporary password: '.$password);
            $this->warn('Store it securely and change it after the first login.');
        }

        return self::SUCCESS;
    }

    private function password(): string
    {
        if ($password = $this->option('password')) {
            return (string) $password;
        }

        if ($this->option('generate-password')) {
            return Str::password(20, symbols: true);
        }

        return (string) $this->secret('Password');
    }

    private function userAttributes(string $password): array
    {
        $attributes = [
            'name' => $this->option('name'),
            'username' => $this->uniqueUsername((string) $this->option('username')),
            'role' => 'super_admin',
            'is_active' => true,
            'password' => $password,
        ];

        foreach ([
            'status' => 'Active',
            'enable_password_login' => true,
            'enable_otp_login' => true,
            'enable_magic_link_login' => true,
            'date_joined' => now()->toDateString(),
            'password_changed_at' => now(),
            'force_password_change' => false,
            'failed_login_attempts' => 0,
            'locked_at' => null,
        ] as $column => $value) {
            if (Schema::hasColumn('users', $column)) {
                $attributes[$column] = $value;
            }
        }

        return $attributes;
    }

    private function usersTableIsReady(): bool
    {
        try {
            if (! Schema::hasTable('users')) {
                $this->components->error('The users table does not exist. Run migrations first.');

                return false;
            }

            $missing = collect(['name', 'email', 'password', 'username', 'role', 'is_active'])
                ->reject(fn (string $column) => Schema::hasColumn('users', $column))
                ->values();
        } catch (\Throwable $e) {
            $this->components->error('Could not inspect the users table: '.$e->getMessage());
            $this->line('Check DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, and DB_PASSWORD.');

            return false;
        }

        if ($missing->isNotEmpty()) {
            $this->components->error('The users table is missing required columns: '.$missing->join(', '));
            $this->line('Run php artisan migrate --force, then run this command again.');

            return false;
        }

        return true;
    }

    private function uniqueUsername(string $username): string
    {
        $username = Str::of($username)->ascii()->lower()->replaceMatches('/[^a-z0-9._-]+/', '.')->trim('.')->value() ?: 'superadmin';

        $query = User::where('username', $username);
        if ($email = $this->argument('email')) {
            $query->where('email', '!=', Str::lower(trim($email)));
        }

        if (! Schema::hasColumn('users', 'username') || ! $query->exists()) {
            return $username;
        }

        return $username.'-'.Str::lower(Str::random(6));
    }

    private function attachToExistingWorkspaces(User $user, IamService $iam): void
    {
        if (! Schema::hasTable('tenants') || ! Schema::hasTable('businesses')) {
            return;
        }

        Tenant::query()->each(function (Tenant $tenant) use ($user, $iam) {
            ActiveTenant::switchTo($tenant);

            $tenant->users()->syncWithoutDetaching([
                $user->id => [
                    'role' => 'owner',
                    'status' => 'active',
                    'joined_at' => now(),
                ],
            ]);

            Business::withoutGlobalScopes()->where('tenant_id', $tenant->id)->each(function (Business $business) use ($user, $iam) {
                ActiveBusiness::switchTo($business);
                $iam->bootstrapBusinessDefaults($user);

                if (! Schema::hasTable('business_user')) {
                    return;
                }

                DB::table('business_user')->updateOrInsert(
                    ['business_id' => $business->id, 'user_id' => $user->id],
                    [
                        'iam_role_id' => IamRole::where('business_id', $business->id)->where('slug', 'system-administrator')->value('id'),
                        'status' => 'Active',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            });
        });
    }

    private function detachFromWorkspaces(User $user): void
    {
        if (Schema::hasTable('tenant_user')) {
            DB::table('tenant_user')->where('user_id', $user->id)->delete();
        }

        if (Schema::hasTable('business_user')) {
            DB::table('business_user')->where('user_id', $user->id)->delete();
        }
    }
}
