<?php

namespace Modules\Fitness\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Client;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Fitness\Models\Member;
use Modules\Fitness\Models\MemberMembership;
use Modules\Fitness\Models\MembershipPlan;
use Modules\Fitness\Services\FitnessFeatureGate;
use Modules\Fitness\Services\FitnessQrCodeService;
use Modules\Fitness\Services\MembershipService;

class MemberController extends Controller
{
    public function index(FitnessFeatureGate $gate)
    {
        $gate->authorize('members');

        $members = Member::with([
            'client',
            'assignedTrainer',
            'activeMembership.plan',
            'memberships' => fn ($query) => $query->with([
                'plan',
                'invoice.payments.paymentMethod',
                'invoice.receipts',
                'payments.paymentMethod',
                'freezes',
                'events.user',
            ])->latest(),
        ])->latest()->paginate(20);

        $memberIds = $members->getCollection()->pluck('id')->all();

        return view('fitness.members', [
            'members' => $members,
            'clients' => Client::orderBy('name')->limit(250)->get(),
            'trainers' => User::where('role', '!=', 'client_portal')->where('is_active', true)->orderBy('name')->get(),
            'plans' => MembershipPlan::where('status', 'Active')->orderBy('name')->get(),
            'memberships' => MemberMembership::with('member.client', 'plan')->latest()->limit(50)->get(),
            'attendanceHistory' => $this->attendanceHistory($memberIds),
            'classBookingHistory' => $this->classBookingHistory($memberIds),
            'statuses' => Member::STATUSES,
            'paymentMethods' => PaymentMethod::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, MembershipService $memberships, FitnessFeatureGate $gate)
    {
        $gate->authorize('members');

        $data = $request->validate([
            'client_mode' => ['required', 'in:existing,new'],
            'client_id' => ['required_if:client_mode,existing', 'nullable', Rule::exists('clients', 'id')->where('business_id', ActiveBusiness::id())],
            'client.name' => ['required_if:client_mode,new', 'nullable', 'string', 'max:255'],
            'client.email' => ['nullable', 'email', 'max:255'],
            'client.phone' => ['nullable', 'string', 'max:100'],
            'client.address' => ['nullable', 'string'],
            'assigned_trainer_id' => ['nullable', 'exists:users,id'],
            'gender' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:100'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'join_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(Member::STATUSES)],
        ]);

        $memberData = collect($data)->except(['client', 'client_mode'])->filter(fn ($value) => $value !== null && $value !== '')->all();
        if (($data['client_mode'] ?? 'new') === 'new') {
            unset($memberData['client_id']);
        }

        $member = $memberships->createMember($data['client'] ?? [], $memberData);

        return back()->with('status', 'Member '.$member->member_number.' created.');
    }

    public function update(Request $request, Member $member, FitnessFeatureGate $gate)
    {
        $gate->authorize('members');

        $data = $request->validate([
            'assigned_trainer_id' => ['nullable', 'exists:users,id'],
            'gender' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:100'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(Member::STATUSES)],
        ]);

        $member->update($data);

        return back()->with('status', 'Member updated.');
    }

    public function card(Member $member, FitnessFeatureGate $gate, FitnessQrCodeService $qrCodes)
    {
        $gate->authorize('members');

        $member->setRelation('client', Client::withoutGlobalScopes()->find($member->client_id));
        $member->setRelation('assignedTrainer', $member->assigned_trainer_id ? User::find($member->assigned_trainer_id) : null);

        $membership = MemberMembership::withoutGlobalScopes()
            ->where('business_id', $member->business_id)
            ->where('fitness_member_id', $member->id)
            ->where('status', 'Active')
            ->latest('id')
            ->first();

        if ($membership) {
            $membership->setRelation('plan', MembershipPlan::withoutGlobalScopes()->find($membership->fitness_membership_plan_id));
        }

        return view('fitness.member-card', [
            'member' => $member,
            'membership' => $membership,
            'cardBusiness' => Business::withoutGlobalScopes()->find($member->business_id),
            'qrCode' => $qrCodes->dataUri($member->qr_code, 210),
        ]);
    }

    private function attendanceHistory(array $memberIds): \Illuminate\Support\Collection
    {
        if ($memberIds === []) {
            return collect();
        }

        return DB::table('fitness_attendance_logs')
            ->where('business_id', ActiveBusiness::id())
            ->whereIn('fitness_member_id', $memberIds)
            ->latest('entry_time')
            ->limit(250)
            ->get()
            ->groupBy('fitness_member_id');
    }

    private function classBookingHistory(array $memberIds): \Illuminate\Support\Collection
    {
        if ($memberIds === []) {
            return collect();
        }

        return DB::table('fitness_class_bookings')
            ->join('fitness_class_sessions', 'fitness_class_sessions.id', '=', 'fitness_class_bookings.fitness_class_session_id')
            ->join('fitness_classes', 'fitness_classes.id', '=', 'fitness_class_sessions.fitness_class_id')
            ->where('fitness_class_bookings.business_id', ActiveBusiness::id())
            ->whereIn('fitness_class_bookings.fitness_member_id', $memberIds)
            ->select(
                'fitness_class_bookings.*',
                'fitness_classes.name as class_name',
                'fitness_class_sessions.starts_at'
            )
            ->latest('fitness_class_sessions.starts_at')
            ->limit(250)
            ->get()
            ->groupBy('fitness_member_id');
    }
}
