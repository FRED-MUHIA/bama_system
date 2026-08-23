<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ActiveTenant
{
    public const SESSION_KEY = 'active_tenant_id';

    private static ?Tenant $current = null;

    private static ?Tenant $fallback = null;

    private static ?int $id = null;

    private static bool $idResolved = false;

    private static array $tableExists = [];

    private static array $columnExists = [];

    public static function current(): ?Tenant
    {
        if (! self::hasTable('tenants')) {
            return null;
        }

        if (self::$current) {
            return self::$current;
        }

        $id = self::id();

        return self::$current = $id ? Tenant::withoutGlobalScopes()->find($id) : (Auth::check() ? null : self::fallback());
    }

    public static function id(): ?int
    {
        if (self::$idResolved) {
            return self::$id;
        }

        self::$idResolved = true;

        if (! self::hasTable('tenants')) {
            return null;
        }

        $user = Auth::user();

        if ($user && in_array($user->role, ['super_admin', 'client_portal'], true)) {
            self::clear();

            return self::$id = null;
        }

        if ($sessionId = Session::get(self::SESSION_KEY)) {
            $sessionId = (int) $sessionId;
            if (! $user || self::userCanAccessTenant($user, $sessionId)) {
                return self::$id = $sessionId;
            }

            Session::forget(self::SESSION_KEY);
        }

        if ($user?->current_tenant_id && self::userCanAccessTenant($user, (int) $user->current_tenant_id)) {
            return self::$id = (int) $user->current_tenant_id;
        }

        if ($user && self::hasTable('tenant_user')) {
            $tenantId = self::firstTenantIdFor($user);
            if ($tenantId) {
                self::switchTo(Tenant::withoutGlobalScopes()->find($tenantId));

                return self::$id = (int) $tenantId;
            }

            return self::$id = null;
        }

        return self::$id = self::fallback()?->id;
    }

    public static function switchTo(?Tenant $tenant): void
    {
        if (! $tenant) {
            self::clear();

            return;
        }

        Session::put(self::SESSION_KEY, $tenant->id);
        self::$current = $tenant;
        self::$id = (int) $tenant->id;
        self::$idResolved = true;

        if (Auth::check() && self::hasColumn('users', 'current_tenant_id')) {
            Auth::user()->forceFill(['current_tenant_id' => $tenant->id])->saveQuietly();
        }
    }

    public static function clear(): void
    {
        Session::forget(self::SESSION_KEY);
        self::$current = null;
        self::$id = null;
        self::$idResolved = true;
    }

    public static function fromRequest(): ?Tenant
    {
        if (! self::hasTable('tenants')) {
            return null;
        }

        $host = Request::getHost();
        $header = Request::header('X-Tenant');

        return Tenant::withoutGlobalScopes()
            ->when($header, fn ($query) => $query->where('slug', $header))
            ->when(! $header && $host, fn ($query) => $query->where('primary_domain', $host))
            ->first();
    }

    public static function fallback(): ?Tenant
    {
        if (self::$fallback) {
            return self::$fallback;
        }

        if (! self::hasTable('tenants')) {
            return null;
        }

        return self::$fallback = Tenant::withoutGlobalScopes()->where('status', '!=', 'suspended')->orderBy('id')->first();
    }

    public static function firstTenantFor(User $user): ?Tenant
    {
        $tenantId = self::firstTenantIdFor($user);

        return $tenantId ? Tenant::withoutGlobalScopes()->find($tenantId) : null;
    }

    public static function userCanAccessTenant(User $user, int $tenantId): bool
    {
        if ($user->role === 'super_admin') {
            return false;
        }

        if (! self::hasTable('tenant_user')) {
            return false;
        }

        return DB::table('tenant_user')
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    private static function firstTenantIdFor(User $user): ?int
    {
        if ($user->role === 'super_admin' || ! self::hasTable('tenant_user')) {
            return null;
        }

        $tenantId = DB::table('tenant_user')
            ->join('tenants', 'tenants.id', '=', 'tenant_user.tenant_id')
            ->where('tenant_user.user_id', $user->id)
            ->where('tenant_user.status', 'active')
            ->where('tenants.status', '!=', 'suspended')
            ->whereNull('tenants.deleted_at')
            ->orderBy('tenants.id')
            ->value('tenants.id');

        return $tenantId ? (int) $tenantId : null;
    }

    public static function slug(string $name): string
    {
        $base = Str::slug($name) ?: 'tenant';
        $slug = $base;
        $counter = 2;

        while (self::hasTable('tenants') && Tenant::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private static function hasTable(string $table): bool
    {
        return self::$tableExists[$table] ??= Schema::hasTable($table);
    }

    private static function hasColumn(string $table, string $column): bool
    {
        $key = $table.'.'.$column;

        return self::$columnExists[$key] ??= Schema::hasColumn($table, $column);
    }
}
