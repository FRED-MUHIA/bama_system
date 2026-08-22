<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
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

        return self::$current = $id ? Tenant::withoutGlobalScopes()->find($id) : self::fallback();
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

        if ($sessionId = Session::get(self::SESSION_KEY)) {
            return self::$id = (int) $sessionId;
        }

        $user = Auth::user();
        if ($user?->current_tenant_id) {
            return self::$id = (int) $user->current_tenant_id;
        }

        if ($user && self::hasTable('tenant_user')) {
            $tenantId = $user->tenants()->wherePivot('status', 'active')->value('tenants.id');
            if ($tenantId) {
                self::switchTo(Tenant::withoutGlobalScopes()->find($tenantId));
                return self::$id = (int) $tenantId;
            }
        }

        return self::$id = self::fallback()?->id;
    }

    public static function switchTo(?Tenant $tenant): void
    {
        if (! $tenant) {
            Session::forget(self::SESSION_KEY);
            self::$current = null;
            self::$id = null;
            self::$idResolved = true;
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
