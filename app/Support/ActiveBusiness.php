<?php

namespace App\Support;

use App\Models\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ActiveBusiness
{
    public const SESSION_KEY = 'active_business_id';

    private static ?Business $current = null;

    private static ?Business $default = null;

    public static function current(): ?Business
    {
        if (! SchemaCache::hasTable('businesses')) {
            self::clear();

            return null;
        }

        $user = auth()->user();
        if ($user && in_array($user->role, ['super_admin', 'client_portal'], true)) {
            self::clear();

            return null;
        }

        $sessionId = Session::get(self::SESSION_KEY);

        if (self::$current && (! $sessionId || (int) $sessionId === (int) self::$current->id)) {
            return self::$current;
        }

        $accessibleIds = self::accessibleBusinessIds();

        if ($accessibleIds !== null && $accessibleIds === []) {
            if ($sessionId && Business::withoutGlobalScopes()->whereKey($sessionId)->exists()) {
                $accessibleIds = [(int) $sessionId];
            } else {
                Session::forget(self::SESSION_KEY);

                return self::$current = null;
            }
        }

        $business = $accessibleIds === null ? self::ensureDefaults() : null;
        $activeId = $sessionId ?: ($accessibleIds[0] ?? $business?->id);

        if ($accessibleIds !== null && $activeId && ! in_array((int) $activeId, $accessibleIds, true)) {
            $activeId = $accessibleIds[0] ?? null;
            if ($activeId) {
                Session::put(self::SESSION_KEY, $activeId);
            } else {
                Session::forget(self::SESSION_KEY);
            }
        }

        if ($accessibleIds !== null && ! $activeId) {
            return self::$current = null;
        }

        if ($activeId && ! $sessionId) {
            Session::put(self::SESSION_KEY, $activeId);
        }

        $query = Business::where('is_active', true);
        if ($accessibleIds !== null) {
            $query->whereIn('id', $accessibleIds);
        }

        return self::$current = $query->find($activeId) ?: ($accessibleIds !== null ? $query->orderBy('name')->first() : $business);
    }

    public static function id(): ?int
    {
        return self::current()?->id;
    }

    public static function switchTo(Business $business): void
    {
        Session::put(self::SESSION_KEY, $business->id);
        self::$current = $business;
    }

    public static function clear(): void
    {
        Session::forget(self::SESSION_KEY);
        self::$current = null;
        self::$default = null;
    }

    public static function ensureDefaults(): ?Business
    {
        if (! SchemaCache::hasTable('businesses')) {
            return null;
        }

        if (self::$default) {
            return self::$default;
        }

        $tenantId = ActiveTenant::id();
        if (! $tenantId) {
            return null;
        }

        $tenantBusinesses = Business::withoutGlobalScopes()->where('tenant_id', $tenantId);
        if (! $tenantBusinesses->exists()) {
            $name = ActiveTenant::current()?->name ?? 'Bama';
            Business::create(['tenant_id' => $tenantId, 'name' => $name, 'slug' => self::slug($name)]);
        }

        return self::$default = Business::where('is_active', true)->orderBy('id')->first();
    }

    public static function slug(string $name): string
    {
        $base = Str::slug($name) ?: 'business';
        $slug = $base;
        $counter = 2;

        while (SchemaCache::hasTable('businesses') && Business::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    public static function accessibleBusinessIds(): ?array
    {
        $user = auth()->user();

        if (! $user || $user->role === 'super_admin' || ! SchemaCache::hasTable('business_user')) {
            return null;
        }

        if ($user->role === 'client_portal') {
            return [];
        }

        $ids = DB::table('business_user')
            ->where('user_id', $user->id)
            ->whereIn('status', ['Active', 'Pending Invitation'])
            ->pluck('business_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $ids;
    }
}
