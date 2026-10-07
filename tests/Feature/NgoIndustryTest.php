<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Tenant as PlatformTenant;
use App\Models\User;
use App\Services\IamService;
use App\Services\IndustrySetupService;
use App\Services\ModuleRegistry;
use App\Services\NavigationManager;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\NGO\Models\NgoDonor;
use Modules\NGO\Models\NgoProgram;
use Modules\NGO\Models\NgoSector;
use Tests\TestCase;

class NgoIndustryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PlatformTenant $tenant;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = PlatformTenant::create([
            'name' => 'Hope Network', 'slug' => 'hope-network', 'industry' => 'ngo',
            'sub_industry' => 'local-ngo', 'status' => 'active',
        ]);
        ActiveTenant::switchTo($this->tenant);
        $this->business = Business::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id, 'name' => 'Hope Network',
            'slug' => 'hope-network', 'industry' => 'ngo', 'is_active' => true,
        ]);
        ActiveBusiness::switchTo($this->business);
        app(ModuleRegistry::class)->enableDefaultsFor($this->tenant);

        $this->admin = User::factory()->create([
            'role' => 'admin', 'is_active' => true, 'status' => 'Active',
            'current_tenant_id' => $this->tenant->id,
        ]);
        $this->actingAs($this->admin)->withSession([
            ActiveTenant::SESSION_KEY => $this->tenant->id,
            ActiveBusiness::SESSION_KEY => $this->business->id,
        ]);
        app(IamService::class)->bootstrap();
    }

    protected function tearDown(): void
    {
        foreach ([ActiveTenant::class => ['current', 'fallback', 'id', 'idResolved'], ActiveBusiness::class => ['current', 'default']] as $class => $properties) {
            $reflection = new \ReflectionClass($class);
            foreach ($properties as $property) {
                $ref = $reflection->getProperty($property);
                $ref->setAccessible(true);
                $ref->setValue(null, $property === 'idResolved' ? false : null);
            }
        }

        parent::tearDown();
    }

    public function test_ngo_is_registered_and_exposes_tenant_scoped_operations(): void
    {
        $industry = app(IndustrySetupService::class)->find('ngo');
        $this->assertNotNull($industry);
        $this->assertContains('local-ngo', collect($industry['sub_industries'])->pluck('slug')->all());

        $labels = app(NavigationManager::class)->sidebar()->pluck('label')->all();
        $this->assertContains('Programs', $labels);
        $this->assertContains('Activities', $labels);
        $this->assertContains('Beneficiaries', $labels);

        $this->get(route('ngo.dashboard'))->assertOk()->assertSee('NGO &amp; Non-Profit Executive Workspace', false);
        $this->get(route('ngo.programs.index'))->assertOk()->assertSee('Create program');
        $this->get(route('ngo.donors.index'))->assertOk()->assertSee('Add donor');
    }

    public function test_program_activity_beneficiary_and_donor_workflows_save_scoped_records(): void
    {
        $sector = NgoSector::create(['name' => 'Community Health']);
        $this->post(route('ngo.donors.store'), [
            'name' => 'Global Health Fund', 'type' => 'Foundation', 'country' => 'Kenya', 'status' => 'Active',
        ])->assertSessionHasNoErrors();
        $donor = NgoDonor::firstOrFail();

        $this->post(route('ngo.programs.store'), [
            'name' => 'Community Health Improvement', 'ngo_sector_id' => $sector->id,
            'donor_id' => $donor->id, 'currency' => 'KES', 'status' => 'Active',
            'budget' => 120000, 'starts_on' => today()->toDateString(),
        ])->assertSessionHasNoErrors();
        $program = NgoProgram::firstOrFail();

        $this->post(route('ngo.activities.store'), [
            'name' => 'Community screening', 'program_id' => $program->id,
            'status' => 'Planned', 'starts_on' => today()->addDays(2)->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->post(route('ngo.beneficiaries.store'), [
            'name' => 'Test Household', 'type' => 'Household', 'registration_status' => 'Registered', 'program_id' => $program->id,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ngo_programs', ['business_id' => $this->business->id, 'donor_id' => $donor->id, 'name' => 'Community Health Improvement']);
        $this->assertDatabaseHas('ngo_activities', ['business_id' => $this->business->id, 'program_id' => $program->id, 'name' => 'Community screening']);
        $this->assertDatabaseHas('ngo_beneficiaries', ['business_id' => $this->business->id, 'name' => 'Test Household']);
        $this->assertDatabaseHas('ngo_program_beneficiaries', ['business_id' => $this->business->id, 'program_id' => $program->id, 'status' => 'Enrolled']);
    }

    public function test_ngo_records_are_hidden_when_a_different_tenant_is_active(): void
    {
        $otherTenant = PlatformTenant::create(['name' => 'Other NGO', 'slug' => 'other-ngo', 'industry' => 'ngo', 'status' => 'active']);
        $otherBusiness = Business::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id, 'name' => 'Other NGO', 'slug' => 'other-ngo', 'industry' => 'ngo', 'is_active' => true,
        ]);
        NgoDonor::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id, 'business_id' => $otherBusiness->id,
            'name' => 'Private Other Donor', 'type' => 'Individual', 'status' => 'Active',
        ]);

        $this->get(route('ngo.donors.index'))->assertOk()->assertDontSee('Private Other Donor');
        $this->assertSame(0, NgoDonor::count());
    }
}
