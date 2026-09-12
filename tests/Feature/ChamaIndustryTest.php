<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Tenant as PlatformTenant;
use App\Models\User;
use App\Services\IamService;
use App\Services\ModuleRegistry;
use App\Services\NavigationManager;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChamaIndustryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private PlatformTenant $tenant;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = PlatformTenant::create([
            'name' => 'Changa',
            'slug' => 'changa',
            'industry' => 'chama',
            'sub_industry' => 'standard',
            'status' => 'active',
        ]);
        ActiveTenant::switchTo($this->tenant);

        $this->business = Business::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Changa',
            'slug' => 'changa',
            'industry' => 'chama',
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

    public function test_chama_sidebar_dashboard_and_redirect_are_available(): void
    {
        $labels = app(NavigationManager::class)->sidebar()->pluck('label')->all();

        $this->assertContains('Dashboard', $labels);
        $this->assertContains('Members', $labels);
        $this->assertContains('Contributions', $labels);
        $this->assertContains('Savings', $labels);
        $this->assertContains('Table Banking', $labels);
        $this->assertContains('Loans', $labels);
        $this->assertContains('Welfare', $labels);
        $this->assertContains('Meetings', $labels);
        $this->assertContains('Reports', $labels);

        $this->get(route('chama.dashboard'))
            ->assertOk()
            ->assertSee('Chama Management')
            ->assertSee('Chama Standard')
            ->assertSee('Total Contributions')
            ->assertSee('Quick Setup');

        $this->get(route('chama.dashboard', ['section' => 'members']))
            ->assertOk()
            ->assertSee('Member Profile')
            ->assertSee('Member Register');

        $this->get(route('chama.dashboard', ['section' => 'table-banking']))
            ->assertOk()
            ->assertSee('Table Banking')
            ->assertSee('Current Records');

        $this->get(route('chama.dashboard', ['section' => 'reports']))
            ->assertOk()
            ->assertSee('Chama Reports')
            ->assertSee('Loan');

        $this->get(route('dashboard'))
            ->assertRedirect(route('chama.dashboard'));
    }

    public function test_app_shell_refreshes_chama_permissions_before_sidebar_builds(): void
    {
        DB::table('iam_permissions')->where('name', 'chama.dashboard')->delete();

        $this->assertDatabaseMissing('iam_permissions', ['name' => 'chama.dashboard']);

        $this->get(route('profile.edit'))
            ->assertOk();

        $this->assertDatabaseHas('iam_permissions', ['name' => 'chama.dashboard']);

        $labels = app(NavigationManager::class)->sidebar()->pluck('label')->all();
        $this->assertContains('Members', $labels);
        $this->assertContains('Table Banking', $labels);
        $this->assertContains('Loans', $labels);
    }
}
