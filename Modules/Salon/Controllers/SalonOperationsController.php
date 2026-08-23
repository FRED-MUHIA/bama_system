<?php

namespace Modules\Salon\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Salon\Contracts\SalonSpaServiceContract;
use Modules\Salon\Models\Appointment;
use Modules\Salon\Models\ClientProfile;
use Modules\Salon\Models\Commission;
use Modules\Salon\Models\Consultation;
use Modules\Salon\Models\GiftCard;
use Modules\Salon\Models\LoyaltyAccount;
use Modules\Salon\Models\Membership;
use Modules\Salon\Models\MembershipPlan;
use Modules\Salon\Models\ProductConsumption;
use Modules\Salon\Models\Resource;
use Modules\Salon\Models\Service;
use Modules\Salon\Models\StaffProfile;
use Modules\Salon\Models\StaffSchedule;
use Modules\Salon\Models\Treatment;
use Modules\Salon\Models\WellnessEnrollment;
use Modules\Salon\Models\WellnessProgram;
use Modules\Salon\Repositories\SalonRepository;
use Modules\Salon\Services\SalonFeatureGate;

class SalonOperationsController extends Controller
{
    public function appointments(SalonRepository $repository, SalonFeatureGate $gate)
    {
        $gate->authorize('appointments');

        return $this->view('Appointments', 'Appointments, consultations, treatment flow, rooms/chairs, and booking channels.', [
            'appointmentPage' => true,
            'appointments' => $repository->upcomingAppointments(30)->get(),
            'clients' => ClientProfile::with('client')->latest()->limit(50)->get(),
            'staff' => StaffProfile::where('status', 'Active')->orderBy('display_name')->get(),
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
            'resources' => Resource::where('status', 'Available')->orderBy('name')->get(),
        ]);
    }

    public function storeAppointment(Request $request, SalonSpaServiceContract $salon)
    {
        $data = $request->validate([
            'salon_client_profile_id' => ['required', 'exists:salon_client_profiles,id'],
            'salon_staff_profile_id' => ['nullable', 'exists:salon_staff_profiles,id'],
            'salon_resource_id' => ['nullable', 'exists:salon_resources,id'],
            'starts_at' => ['required', 'date'],
            'channel' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
            'services' => ['required', 'array', 'min:1'],
            'services.*.service_id' => ['required', 'exists:salon_services,id'],
            'services.*.salon_staff_profile_id' => ['nullable', 'exists:salon_staff_profiles,id'],
        ]);

        $profile = ClientProfile::findOrFail($data['salon_client_profile_id']);
        $data['client_id'] = $profile->client_id;
        $salon->bookAppointment($data);

        return back()->with('success', 'Appointment booked.');
    }

    public function completeAppointment(Appointment $appointment, SalonSpaServiceContract $salon)
    {
        $salon->completeAppointment($appointment, ['payment_status' => 'Paid']);

        return back()->with('success', 'Appointment completed and loyalty/commission updated.');
    }

    public function clients(SalonFeatureGate $gate)
    {
        $gate->authorize('loyalty');

        return $this->view('Client Profiles', 'Beauty history, allergies, preferences, membership status, and loyalty value.', [
            'clients' => ClientProfile::with('client', 'loyaltyAccount', 'memberships.plan')->latest()->paginate(20),
        ]);
    }

    public function storeClient(Request $request, SalonSpaServiceContract $salon)
    {
        $salon->createClientProfile($request->validate([
            'name' => ['required_without:client_id', 'nullable', 'string', 'max:255'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string'],
        ]));

        return back()->with('success', 'Salon client profile created.');
    }

    public function staff(SalonFeatureGate $gate)
    {
        $gate->authorize('staff');

        return $this->view('Staff Scheduling', 'Teams, shifts, capacity, services, commissions, and branch assignment.', [
            'staff' => StaffProfile::with('schedules')->latest()->paginate(20),
            'users' => $this->activeBusinessUsers()->orderBy('name')->get(),
            'schedules' => StaffSchedule::with('staff')->whereDate('work_date', '>=', today())->orderBy('work_date')->limit(50)->get(),
        ]);
    }

    public function storeStaff(Request $request, SalonSpaServiceContract $salon)
    {
        $salon->createStaffProfile($request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'user_id' => ['nullable', $this->activeBusinessUserExistsRule()],
            'branch_id' => ['nullable', 'integer'],
            'role_title' => ['nullable', 'string', 'max:120'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'weekly_capacity_minutes' => ['nullable', 'integer', 'min:0'],
        ]));

        return back()->with('success', 'Staff profile created.');
    }

    public function services(SalonRepository $repository, SalonFeatureGate $gate)
    {
        $gate->authorize('services');

        return $this->view('Services & Packages', 'Service catalogue, durations, tax, packages, and treatment templates.', [
            'services' => $repository->activeServices()->paginate(30),
            'packages' => \Modules\Salon\Models\Package::latest()->limit(20)->get(),
        ]);
    }

    public function storeService(Request $request, SalonSpaServiceContract $salon)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'duration_hours' => ['nullable', 'integer', 'min:0', 'max:24'],
            'duration_minutes_part' => ['nullable', 'integer', 'min:0', 'max:59'],
            'price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'requires_consultation' => ['nullable', 'boolean'],
        ]);

        $duration = ((int) ($data['duration_hours'] ?? 0) * 60) + (int) ($data['duration_minutes_part'] ?? 0);
        abort_if($duration < 5, 422, 'Service duration must be at least 5 minutes.');

        unset($data['duration_hours'], $data['duration_minutes_part']);
        $data['duration_minutes'] = $duration;

        $salon->createService($data);

        return back()->with('success', 'Service created.');
    }

    public function memberships(SalonFeatureGate $gate)
    {
        $gate->authorize('memberships');

        return $this->view('Memberships', 'Recurring plans, visit allowances, member balances, and plan benefits.', [
            'plans' => MembershipPlan::latest()->get(),
            'memberships' => Membership::with('profile.client', 'plan')->latest()->paginate(20),
            'membershipClients' => ClientProfile::with('client', 'loyaltyAccount')->where('status', 'Active')->orderByDesc('created_at')->limit(100)->get(),
        ]);
    }

    public function storeMembershipPlan(Request $request, SalonSpaServiceContract $salon)
    {
        $salon->createMembershipPlan($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'billing_cycle' => ['required', 'in:Weekly,Monthly,Quarterly,Annual'],
            'price' => ['required', 'numeric', 'min:0'],
            'visit_allowance' => ['nullable', 'integer', 'min:0'],
            'discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'benefits' => ['nullable', 'string'],
        ]));

        return back()->with('success', 'Membership plan created.');
    }

    public function storeMembership(Request $request, SalonSpaServiceContract $salon)
    {
        $salon->enrollMembership($request->validate([
            'salon_client_profile_id' => ['required', 'exists:salon_client_profiles,id'],
            'salon_membership_plan_id' => ['required', 'exists:salon_membership_plans,id'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'visits_remaining' => ['nullable', 'integer', 'min:0'],
            'balance' => ['nullable', 'numeric', 'min:0'],
            'bonus_points' => ['nullable', 'integer', 'min:0'],
        ]));

        return back()->with('success', 'Member enrolled into the plan.');
    }

    public function awardMembershipPoints(Request $request, Membership $membership, SalonSpaServiceContract $salon)
    {
        $data = $request->validate([
            'points' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $salon->awardLoyaltyPoints($membership->profile, (int) $data['points'], $data['reason'] ?? 'Membership bonus', $membership->membership_number);

        return back()->with('success', 'Bonus points awarded.');
    }

    public function loyalty(SalonFeatureGate $gate)
    {
        $gate->authorize('loyalty');

        return $this->view('Loyalty & Gift Cards', 'Points, tiers, gift card liability, and retention campaigns.', [
            'loyalty' => LoyaltyAccount::with('profile.client')->latest()->paginate(20),
            'giftCards' => GiftCard::with('client')->latest()->limit(20)->get(),
            'loyaltyClients' => ClientProfile::with('client', 'loyaltyAccount')->where('status', 'Active')->orderByDesc('created_at')->limit(100)->get(),
            'giftCardClients' => Client::orderBy('name')->limit(100)->get(),
        ]);
    }

    public function storeGiftCard(Request $request, SalonSpaServiceContract $salon)
    {
        $salon->issueGiftCard($request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['nullable', 'string', 'size:3'],
            'expires_on' => ['nullable', 'date'],
        ]));

        return back()->with('success', 'Gift card issued.');
    }

    public function awardLoyaltyPoints(Request $request, ClientProfile $profile, SalonSpaServiceContract $salon)
    {
        $data = $request->validate([
            'points' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $salon->awardLoyaltyPoints($profile, (int) $data['points'], $data['reason'] ?? 'Manual loyalty bonus', 'loyalty-page');

        return back()->with('success', 'Loyalty points awarded.');
    }

    public function consultations(SalonFeatureGate $gate)
    {
        $gate->authorize('appointments');

        return $this->view('Beauty Consultations', 'Client needs, contraindications, recommendations, and follow-up plans.', [
            'consultations' => Consultation::with('profile.client', 'staff')->latest()->paginate(20),
            'consultationClients' => ClientProfile::with('client')->where('status', 'Active')->orderByDesc('created_at')->limit(100)->get(),
            'consultationStaff' => StaffProfile::where('status', 'Active')->orderBy('display_name')->get(),
            'consultationAppointments' => Appointment::with('profile.client')->latest()->limit(50)->get(),
        ]);
    }

    public function storeConsultation(Request $request, SalonSpaServiceContract $salon)
    {
        $salon->createConsultation($request->validate([
            'salon_client_profile_id' => ['required', 'exists:salon_client_profiles,id'],
            'salon_appointment_id' => ['nullable', 'exists:salon_appointments,id'],
            'salon_staff_profile_id' => ['nullable', 'exists:salon_staff_profiles,id'],
            'consultation_type' => ['nullable', 'string', 'max:120'],
            'observations' => ['nullable', 'string'],
            'recommendations' => ['nullable', 'string'],
            'contraindications' => ['nullable', 'string'],
            'follow_up_date' => ['nullable', 'date'],
        ]));

        return back()->with('success', 'Consultation added.');
    }

    public function treatments(SalonFeatureGate $gate)
    {
        $gate->authorize('appointments');

        return $this->view('Treatments', 'Treatment history, products used, aftercare, and service outcomes.', [
            'treatments' => Treatment::with('profile.client', 'service', 'staff')->latest()->paginate(20),
            'treatmentClients' => ClientProfile::with('client')->where('status', 'Active')->orderByDesc('created_at')->limit(100)->get(),
            'treatmentStaff' => StaffProfile::where('status', 'Active')->orderBy('display_name')->get(),
            'treatmentServices' => Service::where('is_active', true)->orderBy('name')->get(),
            'treatmentAppointments' => Appointment::with('profile.client')->latest()->limit(50)->get(),
        ]);
    }

    public function storeTreatment(Request $request, SalonSpaServiceContract $salon)
    {
        $salon->createTreatment($request->validate([
            'salon_client_profile_id' => ['required', 'exists:salon_client_profiles,id'],
            'salon_appointment_id' => ['nullable', 'exists:salon_appointments,id'],
            'salon_service_id' => ['nullable', 'exists:salon_services,id'],
            'salon_staff_profile_id' => ['nullable', 'exists:salon_staff_profiles,id'],
            'name' => ['required', 'string', 'max:255'],
            'performed_on' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'products_used' => ['nullable', 'string'],
            'aftercare' => ['nullable', 'string'],
        ]));

        return back()->with('success', 'Treatment added.');
    }

    public function inventory(SalonFeatureGate $gate)
    {
        $gate->authorize('services');

        return $this->view('Product Usage', 'Inventory consumption per appointment and shared stock handoff.', [
            'consumptions' => ProductConsumption::with('appointment', 'product')->latest()->paginate(20),
            'products' => Product::orderBy('name')->limit(100)->get(),
            'inventoryServices' => Service::where('is_active', true)->orderBy('name')->get(),
            'appointments' => Appointment::whereDate('starts_at', '>=', today()->subDays(7))->latest()->limit(30)->get(),
        ]);
    }

    public function storeInventoryConsumption(Request $request, SalonSpaServiceContract $salon)
    {
        $data = $request->validate([
            'salon_appointment_id' => ['required', 'exists:salon_appointments,id'],
            'salon_service_id' => ['nullable', 'exists:salon_services,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit' => ['nullable', 'string', 'max:20'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $appointment = Appointment::findOrFail($data['salon_appointment_id']);
        unset($data['salon_appointment_id']);
        $salon->recordProductConsumption($appointment, $data);

        return back()->with('success', 'Product usage recorded.');
    }

    public function storeConsumption(Request $request, Appointment $appointment, SalonSpaServiceContract $salon)
    {
        $salon->recordProductConsumption($appointment, $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'salon_service_id' => ['nullable', 'exists:salon_services,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit' => ['nullable', 'string', 'max:20'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]));

        return back()->with('success', 'Product consumption recorded and stock updated.');
    }

    public function commissions(SalonFeatureGate $gate)
    {
        $gate->authorize('staff');

        return $this->view('Commissions', 'Staff commission accruals, payout readiness, and productivity.', [
            'commissions' => Commission::with('staff', 'appointment')->latest()->paginate(30),
            'commissionStaff' => StaffProfile::where('status', 'Active')->orderBy('display_name')->get(),
            'commissionAppointments' => Appointment::latest()->limit(50)->get(),
        ]);
    }

    public function storeCommission(Request $request)
    {
        $data = $request->validate([
            'salon_staff_profile_id' => ['required', 'exists:salon_staff_profiles,id'],
            'salon_appointment_id' => ['nullable', 'exists:salon_appointments,id'],
            'commission_date' => ['required', 'date'],
            'base_amount' => ['required', 'numeric', 'min:0'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['nullable', 'in:Pending,Approved,Paid,Void'],
        ]);

        $data['amount'] = round(((float) $data['base_amount'] * (float) $data['rate']) / 100, 2);
        Commission::create($data);

        return back()->with('success', 'Commission recorded.');
    }

    public function updateCommissionStatus(Request $request, Commission $commission, SalonSpaServiceContract $salon)
    {
        $data = $request->validate([
            'status' => ['required', 'in:Pending,Approved,Paid,Void'],
        ]);

        $salon->updateCommissionStatus($commission, $data['status']);

        return back()->with('success', 'Commission status updated.');
    }

    public function wellness(SalonFeatureGate $gate)
    {
        $gate->authorize('services');

        return $this->view('Wellness Programs', 'Program enrolments, milestones, care plans, and progress tracking.', [
            'programs' => WellnessProgram::withCount('enrollments')->latest()->get(),
            'enrollments' => WellnessEnrollment::with('profile.client', 'program')->latest()->limit(20)->get(),
            'wellnessClients' => ClientProfile::with('client')->where('status', 'Active')->orderByDesc('created_at')->limit(100)->get(),
        ]);
    }

    public function storeWellnessProgram(Request $request, SalonSpaServiceContract $salon)
    {
        $salon->createWellnessProgram($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'milestones' => ['nullable', 'string'],
        ]));

        return back()->with('success', 'Wellness program created.');
    }

    public function storeWellnessEnrollment(Request $request, SalonSpaServiceContract $salon)
    {
        $salon->enrollWellnessProgram($request->validate([
            'salon_wellness_program_id' => ['required', 'exists:salon_wellness_programs,id'],
            'salon_client_profile_id' => ['required', 'exists:salon_client_profiles,id'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'progress' => ['nullable', 'string'],
        ]));

        return back()->with('success', 'Client enrolled into wellness program.');
    }

    public function pos(SalonFeatureGate $gate)
    {
        $gate->authorize('pos');

        return redirect()->route('pos-orders.index')->with('success', 'Use the shared POS core for Salon & Spa sales.');
    }

    public function reports(SalonFeatureGate $gate, \Modules\Salon\Services\SalonDashboardService $dashboard)
    {
        $gate->authorize('reports');

        return $this->view('Reports', 'Executive, branch, staff, revenue, stock, client, and compliance reporting.', [
            'metrics' => $dashboard->metrics(),
            'kpis' => $dashboard->kpis(),
            'reports' => $dashboard->reports(),
        ]);
    }

    private function view(string $title, string $description, array $data = [])
    {
        return view('salon.operations', $data + compact('title', 'description'));
    }
}
