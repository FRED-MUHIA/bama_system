<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformAccountSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_search_accounts_and_deleted_accounts_stay_excluded(): void
    {
        $owner = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'status' => 'Active']);
        $tenant = Tenant::create(['name' => 'Acme Client', 'slug' => 'acme-client', 'primary_domain' => 'acme.test', 'status' => 'active']);
        $member = User::factory()->create(['email' => 'accounts@example.test']);
        $tenant->users()->attach($member, ['role' => 'owner', 'status' => 'active']);
        Business::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'name' => 'Corner Shop', 'slug' => 'corner-shop']);
        $deleted = Tenant::create(['name' => 'Acme Deleted', 'slug' => 'acme-deleted']);
        $deleted->delete();
        Tenant::create(['name' => 'Other Client', 'slug' => 'other-client']);

        foreach ([' ACME ', 'ACCOUNTS@EXAMPLE', 'corner', 'acme.test'] as $search) {
            $this->actingAs($owner)->get(route('platform.tenants', ['search' => $search]))
                ->assertOk()
                ->assertSee('Search accounts')
                ->assertViewHas('tenants', fn ($tenants) => $tenants->total() === 1 && $tenants->first()->id === $tenant->id);
        }

        foreach (['no-match', '%', '_'] as $search) {
            $this->get(route('platform.tenants', ['search' => $search]))
                ->assertOk()->assertSee('No accounts match your search.')
                ->assertViewHas('tenants', fn ($tenants) => $tenants->total() === 0);
        }

        $this->get(route('platform.tenants'))->assertOk()
            ->assertViewHas('tenants', fn ($tenants) => $tenants->total() === 2);
    }

    public function test_search_is_preserved_across_pages(): void
    {
        $owner = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'status' => 'Active']);
        for ($i = 1; $i <= 21; $i++) {
            Tenant::create(['name' => "Matching Client $i", 'slug' => "matching-$i"]);
        }

        $this->actingAs($owner)->get(route('platform.tenants', ['search' => 'Matching']))
            ->assertOk()->assertViewHas('tenants', fn ($tenants) => $tenants->total() === 21 && str_contains($tenants->nextPageUrl(), 'search=Matching'));
        $this->get(route('platform.tenants', ['search' => 'Matching', 'page' => 2]))
            ->assertOk()->assertViewHas('tenants', fn ($tenants) => $tenants->count() === 1);
    }
}
