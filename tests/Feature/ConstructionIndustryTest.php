<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tenant as PlatformTenant;
use App\Models\User;
use App\Services\IamService;
use App\Services\ModuleRegistry;
use App\Services\NavigationManager;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Construction\Models\ConstructionBoq;
use Modules\Construction\Models\ConstructionCertificate;
use Modules\Construction\Models\ConstructionMaterial;
use Modules\Construction\Models\ConstructionProjectProfile;
use Modules\Construction\Models\ConstructionTender;
use Modules\Construction\Services\BOQService;
use Modules\Construction\Services\CommercialService;
use Modules\Construction\Services\ConstructionEstimateService;
use Modules\Construction\Services\ConstructionService;
use Modules\Construction\Services\MaterialManagementService;
use Tests\TestCase;

class ConstructionIndustryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PlatformTenant $tenant;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = PlatformTenant::create([
            'name' => 'Build Prime',
            'slug' => 'build-prime',
            'industry' => 'construction',
            'sub_industry' => 'enterprise',
            'status' => 'active',
        ]);
        ActiveTenant::switchTo($this->tenant);

        $this->business = Business::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Build Prime',
            'slug' => 'build-prime',
            'industry' => 'construction',
            'is_active' => true,
        ]);
        ActiveBusiness::switchTo($this->business);
        app(ModuleRegistry::class)->enableDefaultsFor($this->tenant);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'status' => 'Active',
            'current_tenant_id' => $this->tenant->id,
        ]);

        $this->actingAs($this->admin)->withSession([
            ActiveTenant::SESSION_KEY => $this->tenant->id,
            ActiveBusiness::SESSION_KEY => $this->business->id,
        ]);
        app(IamService::class)->bootstrap();
    }

    public function test_construction_package_navigation_dashboard_and_permissions_are_available(): void
    {
        $this->getJson('/api/v1/industry-packages/construction?sub_industry=enterprise')
            ->assertOk()
            ->assertJsonPath('slug', 'construction')
            ->assertJsonPath('industry', 'Construction')
            ->assertJsonPath('selected_sub_industry', 'enterprise')
            ->assertJsonFragment(['BOQ Management'])
            ->assertJsonFragment(['Construction Administrator']);

        $labels = app(NavigationManager::class)->sidebar()->pluck('label')->all();
        $this->assertContains('Dashboard', $labels);
        $this->assertContains('BOQ & Estimating', $labels);
        $this->assertContains('Tenders', $labels);
        $this->assertContains('Materials', $labels);

        $this->get(route('construction.dashboard'))
            ->assertOk()
            ->assertSee('Construction')
            ->assertSee('Active projects');

        $this->get(route('dashboard'))
            ->assertRedirect(route('construction.dashboard'));
    }

    public function test_construction_workflow_boq_estimate_tender_commercial_materials_and_tenant_isolation_work(): void
    {
        $client = Client::create(['name' => 'Metro County', 'type' => 'company', 'email' => 'client@example.test']);

        $profile = app(ConstructionService::class)->createProject([
            'client_id' => $client->id,
            'project_name' => 'County Office Block',
            'contract_type' => 'Main Contract',
            'contract_value' => 25000000,
            'planned_completion' => '2026-12-31',
            'status' => 'Active',
        ]);
        $this->assertMatchesRegularExpression('/^CON-2026-\d{5}$/', $profile->project_number);
        $this->assertSame($this->tenant->id, $profile->tenant_id);
        $this->assertSame($this->business->id, $profile->business_id);

        $boq = app(BOQService::class)->create([
            'project_id' => $profile->project_id,
            'client_id' => $client->id,
            'title' => 'Office Block BOQ',
            'preliminaries' => 5000,
            'contingency' => 2500,
            'tax' => 1600,
        ]);
        $item = app(BOQService::class)->addItem($boq, [
            'description' => 'Concrete works',
            'unit' => 'm3',
            'quantity' => 10,
            'unit_rate' => 1200,
        ]);
        $this->assertSame(12000.0, (float) $item->total_amount);
        $this->assertSame(21100.0, (float) $boq->fresh()->grand_total);

        $estimate = app(ConstructionEstimateService::class)->create([
            'client_id' => $client->id,
            'project_id' => $profile->project_id,
            'boq_id' => $boq->id,
            'title' => 'Office Block Estimate',
            'direct_cost' => 100000,
            'overhead_percentage' => 10,
            'profit_percentage' => 20,
            'tax' => 16000,
        ]);
        $this->assertMatchesRegularExpression('/^EST-2026-\d{5}$/', $estimate->estimate_number);
        $this->assertSame(148000.0, (float) $estimate->selling_price);

        $tender = app(ConstructionEstimateService::class)->convertToTender($estimate);
        $this->assertInstanceOf(ConstructionTender::class, $tender);
        $this->assertSame('Converted', $estimate->fresh()->status);

        $material = app(MaterialManagementService::class)->material([
            'name' => 'Cement',
            'category' => 'Cement',
            'unit' => 'bags',
            'unit_cost' => 750,
            'stock_quantity' => 100,
            'reorder_level' => 25,
            'status' => 'Active',
        ]);
        app(MaterialManagementService::class)->consume([
            'project_id' => $profile->project_id,
            'material_id' => $material->id,
            'usage_date' => '2026-08-21',
            'actual_quantity' => 25,
        ]);
        $this->assertSame(75.0, (float) $material->fresh()->stock_quantity);

        $this->expectException(ValidationException::class);
        app(MaterialManagementService::class)->consume([
            'project_id' => $profile->project_id,
            'material_id' => $material->id,
            'usage_date' => '2026-08-21',
            'actual_quantity' => 500,
        ]);
    }

    public function test_certificates_invoice_adopt_construction_context_and_records_remain_tenant_scoped(): void
    {
        $client = Client::create(['name' => 'Roads Agency', 'type' => 'company']);
        $profile = app(ConstructionService::class)->createProject([
            'client_id' => $client->id,
            'project_name' => 'Access Road',
            'contract_value' => 5000000,
            'status' => 'Active',
        ]);

        $certificate = app(CommercialService::class)->certificate([
            'project_id' => $profile->project_id,
            'client_id' => $client->id,
            'work_executed' => 1000000,
            'retention' => 50000,
            'tax' => 160000,
            'status' => 'Approved',
        ]);
        $invoice = app(CommercialService::class)->invoiceCertificate($certificate);

        $this->assertInstanceOf(ConstructionCertificate::class, $certificate);
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertSame('construction', $invoice->industry_module);
        $this->assertSame($certificate->certificate_number, $invoice->industry_reference);
        $this->assertSame($invoice->id, $certificate->fresh()->invoice_id);

        $otherTenant = PlatformTenant::create([
            'name' => 'Other Builder',
            'slug' => 'other-builder',
            'industry' => 'construction',
            'status' => 'active',
        ]);
        $otherBusiness = Business::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Builder',
            'slug' => 'other-builder',
            'industry' => 'construction',
            'is_active' => true,
        ]);

        ActiveTenant::switchTo($otherTenant);
        ActiveBusiness::switchTo($otherBusiness);
        app(MaterialManagementService::class)->material([
            'name' => 'Hidden Steel',
            'unit' => 'ton',
            'stock_quantity' => 1,
        ]);

        ActiveTenant::switchTo($this->tenant);
        ActiveBusiness::switchTo($this->business);

        $this->assertSame(1, ConstructionProjectProfile::count());
        $this->assertSame(0, ConstructionMaterial::where('name', 'Hidden Steel')->count());
        $this->assertSame(1, ConstructionMaterial::withoutGlobalScopes()->where('name', 'Hidden Steel')->count());
    }
}
