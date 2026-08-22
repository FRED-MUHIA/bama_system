<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Client;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Salon\Contracts\SalonSpaServiceContract;
use Modules\Salon\Models\StaffProfile;
use Tests\TestCase;

class SalonSpaIndustryTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::where('slug', 'bama')->firstOrFail();
        $this->tenant = Tenant::firstOrFail();
        $this->tenant->update(['industry' => 'salon']);
        $this->user = User::factory()->create(['role' => 'admin', 'is_active' => true, 'status' => 'Active', 'current_tenant_id' => $this->tenant->id]);

        $this->actingAs($this->user)->withSession(['active_business_id' => $this->business->id, 'active_tenant_id' => $this->tenant->id]);

        $moduleId = DB::table('modules')->where('slug', 'salon')->value('id');
        DB::table('tenant_modules')->updateOrInsert(
            ['tenant_id' => $this->tenant->id, 'module_id' => $moduleId],
            ['enabled' => true, 'enabled_at' => now(), 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function test_salon_package_can_book_complete_and_consume_stock(): void
    {
        $salon = app(SalonSpaServiceContract::class);
        $client = Client::create(['name' => 'Maya Client', 'phone' => '0700000000']);
        $profile = $salon->createClientProfile(['client_id' => $client->id]);
        $staff = StaffProfile::create(['display_name' => 'Amina Stylist', 'commission_rate' => 10]);
        $service = $salon->createService(['name' => 'Signature Facial', 'duration_minutes' => 60, 'price' => 2500, 'commission_rate' => 12]);
        $product = Product::create(['name' => 'Serum', 'sku' => 'SERUM-1', 'price' => 800, 'cost_price' => 200, 'stock_quantity' => 5, 'reorder_level' => 1]);

        $appointment = $salon->bookAppointment([
            'salon_client_profile_id' => $profile->id,
            'client_id' => $client->id,
            'salon_staff_profile_id' => $staff->id,
            'starts_at' => now()->addDay()->toDateTimeString(),
            'services' => [['service_id' => $service->id]],
        ]);

        $this->assertSame('2500.00', $appointment->total);

        $salon->recordProductConsumption($appointment, ['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 200]);
        $completed = $salon->completeAppointment($appointment);

        $this->assertSame('Completed', $completed->status);
        $this->assertDatabaseHas('salon_loyalty_accounts', ['salon_client_profile_id' => $profile->id, 'points_balance' => 2500]);
        $this->assertDatabaseHas('salon_commissions', ['salon_appointment_id' => $appointment->id, 'amount' => 300]);
        $this->assertSame('4.000', $product->fresh()->stock_quantity);
    }

    public function test_salon_dashboard_api_returns_metrics(): void
    {
        $this->getJson(route('api.v1.salon.dashboard'))
            ->assertOk()
            ->assertJsonPath('data.metrics.Appointments Today', 0)
            ->assertJsonStructure(['data' => ['metrics', 'kpis', 'reports']]);
    }

    public function test_inventory_usage_page_does_not_render_appointment_booking_form(): void
    {
        $this->get(route('salon.inventory.index'))
            ->assertOk()
            ->assertSee('Product Usage')
            ->assertDontSee('Book appointment');
    }

    public function test_membership_plan_enrollment_and_bonus_points_are_recorded(): void
    {
        $salon = app(SalonSpaServiceContract::class);
        $client = Client::create(['name' => 'Maya Member', 'phone' => '0700111222']);
        $profile = $salon->createClientProfile(['client_id' => $client->id]);

        $plan = $salon->createMembershipPlan([
            'name' => 'Glow Monthly',
            'billing_cycle' => 'Monthly',
            'price' => 3500,
            'visit_allowance' => 4,
            'discount_rate' => 10,
            'benefits' => "Priority booking\nFree consultation",
        ]);

        $membership = $salon->enrollMembership([
            'salon_client_profile_id' => $profile->id,
            'salon_membership_plan_id' => $plan->id,
            'starts_on' => '2026-07-28',
            'bonus_points' => 250,
        ]);

        $this->assertSame('Glow Monthly', $membership->plan->name);
        $this->assertSame(4, $membership->visits_remaining);
        $this->assertSame('2026-08-28', $membership->ends_on->toDateString());
        $this->assertSame(['Priority booking', 'Free consultation'], $plan->fresh()->benefits);
        $this->assertDatabaseHas('salon_loyalty_accounts', [
            'salon_client_profile_id' => $profile->id,
            'points_balance' => 250,
            'lifetime_points' => 250,
        ]);

        $salon->awardLoyaltyPoints($profile, 75, 'Referral bonus', $membership->membership_number);

        $this->assertDatabaseHas('salon_loyalty_accounts', [
            'salon_client_profile_id' => $profile->id,
            'points_balance' => 325,
            'lifetime_points' => 325,
        ]);
    }

    public function test_consultations_and_treatments_can_be_recorded(): void
    {
        $salon = app(SalonSpaServiceContract::class);
        $client = Client::create(['name' => 'Maya Treatment Client', 'phone' => '0700333444']);
        $profile = $salon->createClientProfile(['client_id' => $client->id]);
        $staff = StaffProfile::create(['display_name' => 'Grace Therapist']);
        $service = $salon->createService(['name' => 'Skin Therapy', 'duration_minutes' => 75, 'price' => 4200]);

        $consultation = $salon->createConsultation([
            'salon_client_profile_id' => $profile->id,
            'salon_staff_profile_id' => $staff->id,
            'consultation_type' => 'Skin Analysis',
            'observations' => "Dry skin\nSensitive cheeks",
            'recommendations' => "Hydration facial\nSPF routine",
            'contraindications' => 'Avoid retinol for 48 hours',
            'follow_up_date' => '2026-08-04',
        ]);

        $treatment = $salon->createTreatment([
            'salon_client_profile_id' => $profile->id,
            'salon_service_id' => $service->id,
            'salon_staff_profile_id' => $staff->id,
            'name' => 'Hydration facial',
            'performed_on' => '2026-07-28',
            'notes' => 'Client tolerated treatment well.',
            'products_used' => "Cleanser\nHydration mask",
            'aftercare' => "Avoid direct sun\nUse SPF",
        ]);

        $this->assertSame(['Dry skin', 'Sensitive cheeks'], $consultation->observations);
        $this->assertSame('2026-08-04', $consultation->follow_up_date->toDateString());
        $this->assertSame(['Cleanser', 'Hydration mask'], $treatment->products_used);
        $this->assertSame(['Avoid direct sun', 'Use SPF'], $treatment->aftercare);
        $this->assertDatabaseHas('salon_treatments', ['name' => 'Hydration facial']);
    }

    public function test_loyalty_gift_cards_wellness_and_commission_features_work(): void
    {
        $salon = app(SalonSpaServiceContract::class);
        $client = Client::create(['name' => 'Maya Rewards Client', 'phone' => '0700555666']);
        $profile = $salon->createClientProfile(['client_id' => $client->id]);
        $staff = StaffProfile::create(['display_name' => 'Nia Stylist']);

        $giftCard = $salon->issueGiftCard([
            'client_id' => $client->id,
            'amount' => 1500,
            'currency' => 'KES',
            'expires_on' => '2026-12-31',
        ]);

        $program = $salon->createWellnessProgram([
            'name' => 'Glow Reset',
            'category' => 'Skin Wellness',
            'duration_days' => 21,
            'price' => 8000,
            'milestones' => "Consultation\nTreatment\nReview",
        ]);

        $enrollment = $salon->enrollWellnessProgram([
            'salon_wellness_program_id' => $program->id,
            'salon_client_profile_id' => $profile->id,
            'starts_on' => '2026-07-28',
            'progress' => 'Initial consultation booked',
        ]);

        $commission = \Modules\Salon\Models\Commission::create([
            'salon_staff_profile_id' => $staff->id,
            'commission_date' => '2026-07-28',
            'base_amount' => 3000,
            'rate' => 10,
            'amount' => 300,
        ]);

        $salon->awardLoyaltyPoints($profile, 125, 'Retention bonus', $giftCard->card_number);
        $paidCommission = $salon->updateCommissionStatus($commission, 'Paid');

        $this->assertSame('1500.00', $giftCard->balance);
        $this->assertSame(['Consultation', 'Treatment', 'Review'], $program->milestones);
        $this->assertSame('2026-08-18', $enrollment->ends_on->toDateString());
        $this->assertSame('Paid', $paidCommission->status);
        $this->assertDatabaseHas('salon_loyalty_accounts', ['salon_client_profile_id' => $profile->id, 'points_balance' => 125]);
    }
}
