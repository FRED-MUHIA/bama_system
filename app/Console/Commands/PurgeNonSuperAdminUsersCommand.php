<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurgeNonSuperAdminUsersCommand extends Command
{
    protected $signature = 'users:purge-non-super-admins {--force : Delete the accounts instead of showing a dry run}';

    protected $description = 'Delete every non-super-admin user account while preserving platform owner access.';

    public function handle(): int
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            $this->components->error('The users table or role column is missing.');

            return self::FAILURE;
        }

        $superAdminCount = User::where('role', 'super_admin')->count();
        if ($superAdminCount < 1) {
            $this->components->error('No super admin account was found. Refusing to delete users.');

            return self::FAILURE;
        }

        $query = User::query()->where(function ($query) {
            $query->where('role', '!=', 'super_admin')->orWhereNull('role');
        });

        $count = (clone $query)->count();

        if (! $this->option('force')) {
            $this->components->info("Dry run: {$count} non-super-admin account(s) would be deleted.");
            $this->line('Run with --force to delete them.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($query) {
            $userIds = (clone $query)->pluck('id');
            $emails = User::whereIn('id', $userIds)->pluck('email')->filter()->all();

            $this->deleteRows('sessions', 'user_id', $userIds->all());
            $this->deleteRows('otp_codes', 'user_id', $userIds->all());
            $this->deleteRows('login_tokens', 'user_id', $userIds->all());
            $this->deleteRows('password_reset_tokens', 'email', $emails);

            (clone $query)->delete();
        });

        $this->components->info("Deleted {$count} non-super-admin account(s). Super admin accounts kept: {$superAdminCount}.");

        return self::SUCCESS;
    }

    private function deleteRows(string $table, string $column, array $values): void
    {
        if ($values === [] || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)->whereIn($column, $values)->delete();
    }
}
