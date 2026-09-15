<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Tenant;
use App\Models\User;
use App\Services\IamService;
use App\Services\IndustrySetupService;
use App\Services\ModuleRegistry;
use App\Services\NavigationManager;
use App\Services\TenantProvisioningService;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TenantProvisioningServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisioning_generates_business_slugs_against_all_tenants(): void
    {
        $fallbackTenant = Tenant::where('slug', 'bama')->firstOrFail();
        ActiveTenant::switchTo($fallbackTenant);

        $existingTenant = Tenant::create([
            'name' => 'Existing Retailer',
            'slug' => 'existing-retailer',
            'industry' => 'retail',
            'status' => 'active',
        ]);

        Business::withoutGlobalScopes()->create([
            'tenant_id' => $existingTenant->id,
            'name' => 'Mambo',
            'slug' => 'mambo',
            'industry' => 'restaurant',
        ]);

        $owner = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'status' => 'Active',
        ]);

        $tenant = app(TenantProvisioningService::class)->provision([
            'tenant_name' => 'Mambo',
            'business_name' => 'Mambo',
            'industry' => 'restaurant',
            'sub_industry' => 'standard',
            'plan' => 'starter',
        ], $owner);

        $business = Business::withoutGlobalScopes()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->assertSame('mambo-2', $business->slug);
        $this->assertDatabaseHas('businesses', [
            'tenant_id' => $tenant->id,
            'name' => 'Mambo',
            'slug' => 'mambo-2',
        ]);
    }

    public function test_restaurant_tenants_without_routable_industry_menus_see_standard_erp_features(): void
    {
        $tenant = Tenant::create([
            'name' => 'Mambo',
            'slug' => 'mambo-navigation',
            'industry' => 'restaurant',
            'status' => 'active',
        ]);

        ActiveTenant::switchTo($tenant);

        $business = Business::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Mambo',
            'slug' => 'mambo-navigation',
            'industry' => 'restaurant',
        ]);

        ActiveBusiness::switchTo($business);
        app(ModuleRegistry::class)->enableDefaultsFor($tenant);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'status' => 'Active',
        ]);

        $this->actingAs($admin)->withSession([
            ActiveTenant::SESSION_KEY => $tenant->id,
            ActiveBusiness::SESSION_KEY => $business->id,
        ]);

        app(IamService::class)->bootstrap();

        $labels = app(NavigationManager::class)->sidebar()->pluck('label')->all();

        $this->assertContains('Dashboard', $labels);
        $this->assertContains('Clients', $labels);
        $this->assertContains('Projects', $labels);
        $this->assertContains('Products', $labels);
        $this->assertContains('Procurement', $labels);
        $this->assertContains('Cost Accounting', $labels);
        $this->assertContains('Finance', $labels);
        $this->assertContains('Quotations', $labels);
        $this->assertContains('Invoices', $labels);
        $this->assertContains('Receipts', $labels);
        $this->assertContains('Administration', $labels);
    }

    public function test_retail_tenant_provisioning_keeps_selected_store_category(): void
    {
        $owner = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'status' => 'Active',
        ]);

        $tenant = app(TenantProvisioningService::class)->provision([
            'tenant_name' => 'Mtaa Grocers',
            'business_name' => 'Mtaa Grocers',
            'industry' => 'retail',
            'sub_industry' => 'grocery-store',
            'plan' => 'starter',
        ], $owner);

        $dashboard = app(\App\Services\IndustrySetupService::class)->dashboardFeaturesForTenant($tenant->refresh());

        $this->assertSame('grocery-store', $tenant->sub_industry);
        $this->assertSame('grocery-store', $tenant->settings['sub_industry']);
        $this->assertSame('Grocery Store', $dashboard['sub_industry']);
    }

    public function test_registration_provisioning_rejects_existing_account_email_without_database_error(): void
    {
        $tenant = Tenant::where('slug', 'bama')->firstOrFail();

        User::factory()->create([
            'email' => 'govohkenya@gmail.com',
            'current_tenant_id' => $tenant->id,
            'role' => 'admin',
            'is_active' => true,
            'status' => 'Active',
        ]);

        try {
            app(TenantProvisioningService::class)->provisionRegistration([
                'account' => [
                    'name' => 'Govoh Kenya',
                    'email' => 'govohkenya@gmail.com',
                    'phone' => '+254729929118',
                    'password' => 'Password1',
                ],
                'company' => [
                    'company_name' => 'Govoh Kenya',
                    'industry' => 'government',
                    'sub_industry' => 'standard',
                    'country' => 'Kenya',
                    'currency' => 'KES',
                    'timezone' => 'Africa/Nairobi',
                ],
                'plan' => 'professional',
            ]);

            $this->fail('Expected duplicate registration email to be rejected.');
        } catch (ValidationException $e) {
            $this->assertSame('That email is already registered.', $e->errors()['email'][0]);
        }

        $this->assertSame(1, User::where('email', 'govohkenya@gmail.com')->count());
    }

    public function test_registration_provisioning_supports_every_configured_industry(): void
    {
        $industries = app(IndustrySetupService::class);

        foreach ($industries->registrationIndustries() as $industry) {
            $subIndustry = $industries->registrationSubIndustrySlugs($industry['slug'])[0] ?? 'standard';
            $companyName = 'Signup '.$industry['slug'];

            $result = app(TenantProvisioningService::class)->provisionRegistration([
                'account' => [
                    'name' => $companyName.' Owner',
                    'email' => 'signup.'.$industry['slug'].'@example.com',
                    'phone' => null,
                    'password' => 'Password1',
                ],
                'company' => [
                    'company_name' => $companyName,
                    'industry' => $industry['slug'],
                    'sub_industry' => $subIndustry,
                    'country' => 'Kenya',
                    'currency' => 'KES',
                    'timezone' => 'Africa/Nairobi',
                ],
                'plan' => 'professional',
            ]);

            $this->assertSame($industry['slug'], $result['tenant']->industry);
            $this->assertSame($subIndustry, $result['tenant']->sub_industry);
            $this->assertSame($result['tenant']->id, $result['user']->current_tenant_id);
        }
    }

    public function test_hospitality_sidebar_includes_shared_messaging_and_tax_etims(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trrrat',
            'slug' => 'trrrat',
            'industry' => 'hospitality',
            'sub_industry' => 'resort',
            'status' => 'active',
        ]);

        ActiveTenant::switchTo($tenant);

        $business = Business::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Trrrat',
            'slug' => 'trrrat',
            'industry' => 'hospitality',
        ]);

        ActiveBusiness::switchTo($business);
        app(ModuleRegistry::class)->enableDefaultsFor($tenant);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'status' => 'Active',
        ]);

        $this->actingAs($admin)->withSession([
            ActiveTenant::SESSION_KEY => $tenant->id,
            ActiveBusiness::SESSION_KEY => $business->id,
        ]);

        app(IamService::class)->bootstrap();

        $labels = app(NavigationManager::class)->sidebar()->pluck('label')->all();

        $this->assertContains('Dashboard', $labels);
        $this->assertContains('Messaging', $labels);
        $this->assertContains('Tax & ETIMS', $labels);
        $this->assertContains('Finance', $labels);

        $this->get(route('etims.dashboard'))
            ->assertOk()
            ->assertSee('Tax &amp; ETIMS', false)
            ->assertSee('ETIMS Submission Queue');
    }
}
