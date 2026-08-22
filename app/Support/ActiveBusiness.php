<?php

namespace App\Support;

use App\Models\Business;
use App\Support\ActiveTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ActiveBusiness
{
    public const SESSION_KEY = 'active_business_id';

    private static ?Business $current = null;
    private static ?Business $default = null;

    public static function current(): ?Business
    {
        $sessionId = Session::get(self::SESSION_KEY);

        if (self::$current && (! $sessionId || (int) $sessionId === (int) self::$current->id)) {
            return self::$current;
        }

        $business = self::ensureDefaults();
        $activeId = $sessionId ?: $business?->id;
        $accessibleIds = self::accessibleBusinessIds();

        if ($accessibleIds !== null && $activeId && ! in_array((int) $activeId, $accessibleIds, true)) {
            $activeId = $accessibleIds[0] ?? null;
            if ($activeId) {
                Session::put(self::SESSION_KEY, $activeId);
            }
        }

        if ($accessibleIds !== null && ! $activeId) {
            return self::$current = null;
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

    public static function ensureDefaults(): ?Business
    {
        if (self::$default) {
            return self::$default;
        }

        if (! Business::withoutGlobalScopes()->exists()) {
            Business::create(['tenant_id' => ActiveTenant::id(), 'name' => 'BAMA', 'slug' => 'bama']);
            Business::create(['tenant_id' => ActiveTenant::id(), 'name' => 'BAMA INTERIORS', 'slug' => 'bama-interiors']);
        }

        return self::$default = Business::where('is_active', true)->orderBy('id')->first();
    }

    public static function slug(string $name): string
    {
        $base = Str::slug($name) ?: 'business';
        $slug = $base;
        $counter = 2;

        while (Business::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    public static function accessibleBusinessIds(): ?array
    {
        $user = auth()->user();

        if (! $user || $user->role === 'super_admin' || ! Schema::hasTable('business_user')) {
            return null;
        }

        $ids = DB::table('business_user')
            ->where('user_id', $user->id)
            ->whereIn('status', ['Active', 'Pending Invitation'])
            ->pluck('business_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $ids ?: null;
    }
}
