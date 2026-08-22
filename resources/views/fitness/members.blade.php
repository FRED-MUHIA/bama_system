@extends('layouts.app')
@section('title', 'Fitness Members')

@section('content')
@include('fitness.partials.nav')
<style>
    .fitness-form-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
    .fitness-history-row>td{background:#fbfaf8;border-top:0}
    .fitness-history{border:1px solid #e2ded8;border-radius:8px;background:#fff;margin:-6px 0 10px}
    .fitness-history summary{cursor:pointer;display:flex;justify-content:space-between;gap:12px;align-items:center;padding:12px 14px;font-weight:700}
    .fitness-history-summary{display:flex;flex-wrap:wrap;gap:8px;font-size:.84rem;color:#666;font-weight:600}
    .fitness-history-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;padding:0 14px 14px}
    .fitness-history-panel{border-top:1px solid #ece8e1;padding-top:12px;min-width:0}
    .fitness-history-panel h3{font-size:.82rem;letter-spacing:.08em;text-transform:uppercase;color:#777;margin-bottom:8px}
    .fitness-history-item{border-bottom:1px solid #f0ece6;padding:7px 0}
    .fitness-history-item:last-child{border-bottom:0}
    .fitness-history-line{display:flex;justify-content:space-between;gap:8px;align-items:flex-start}
    .fitness-history-note{font-size:.86rem;color:#666}
    @media(max-width:1000px){.fitness-form-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:1100px){.fitness-history-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:640px){.fitness-form-grid,.fitness-history-grid{grid-template-columns:1fr}.fitness-history summary{align-items:flex-start;flex-direction:column}}
</style>

<div class="card p-3 mb-3">
    <h2 class="h5 mb-3">Add Member</h2>
    <form method="post" action="{{ route('fitness.members.store') }}" class="fitness-form-grid">
        @csrf
        <select class="form-select" name="client_mode" id="fitness-client-mode"><option value="new">New CRM client</option><option value="existing">Existing CRM client</option></select>
        <select class="form-select" name="client_id"><option value="">Existing client</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select>
        <input class="form-control" name="client[name]" placeholder="Member name">
        <input class="form-control" name="client[email]" type="email" placeholder="Email">
        <input class="form-control" name="client[phone]" placeholder="Phone">
        <input class="form-control" name="gender" placeholder="Gender">
        <input class="form-control" name="date_of_birth" type="date">
        <input class="form-control" name="occupation" placeholder="Occupation">
        <input class="form-control" name="address" placeholder="Address">
        <input class="form-control" name="emergency_contact_name" placeholder="Emergency contact">
        <input class="form-control" name="emergency_contact_phone" placeholder="Emergency phone">
        <select class="form-select" name="assigned_trainer_id"><option value="">Assigned trainer</option>@foreach($trainers as $trainer)<option value="{{ $trainer->id }}">{{ $trainer->name }}</option>@endforeach</select>
        <input class="form-control" name="join_date" type="date" value="{{ now()->toDateString() }}">
        <select class="form-select" name="status">@foreach($statuses as $status)<option @selected($status === 'Pending')>{{ $status }}</option>@endforeach</select>
        <button class="btn btn-warning">Create Member</button>
    </form>
</div>

<div class="card p-3 mb-3">
    <h2 class="h5 mb-3">Enroll Membership</h2>
    <form method="post" action="{{ route('fitness.member-memberships.store') }}" class="fitness-form-grid">
        @csrf
        <select class="form-select" name="fitness_member_id" required><option value="">Member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->member_number }} - {{ $member->client?->name }}</option>@endforeach</select>
        <select class="form-select" name="fitness_membership_plan_id" required><option value="">Plan</option>@foreach($plans as $plan)<option value="{{ $plan->id }}">{{ $plan->name }} - {{ $plan->currency }} {{ number_format($plan->price, 2) }}</option>@endforeach</select>
        <input class="form-control" name="starts_at" type="date" value="{{ now()->toDateString() }}" required>
        <input class="form-control" name="price_charged" type="number" min="0" step="0.01" placeholder="Override price">
        <input class="form-control" name="joining_fee_charged" type="number" min="0" step="0.01" placeholder="Override joining fee">
        <select class="form-select" name="status">@foreach(\Modules\Fitness\Models\MemberMembership::STATUSES as $status)<option @selected($status === 'Active')>{{ $status }}</option>@endforeach</select>
        <label class="form-check pt-2"><input class="form-check-input" type="checkbox" name="auto_renew" value="1"> Auto renew</label>
        <button class="btn btn-warning">Enroll</button>
    </form>
</div>

<div class="card mb-3">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Member</th><th>Membership</th><th>Trainer</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($members as $member)
                @php($active = $member->activeMembership)
                @php($memberMemberships = $member->memberships->sortByDesc(fn($historyMembership) => $historyMembership->starts_at?->timestamp ?? $historyMembership->id))
                @php($memberVisits = $attendanceHistory->get($member->id, collect()))
                @php($memberBookings = $classBookingHistory->get($member->id, collect()))
                @php($memberPayments = $memberMemberships->flatMap(function ($historyMembership) { $invoicePayments = $historyMembership->invoice?->payments ?? collect(); return $invoicePayments->isNotEmpty() ? $invoicePayments : $historyMembership->payments; })->sortByDesc(fn($payment) => $payment->payment_date?->timestamp ?? $payment->id))
                @php($totalPaid = $memberPayments->sum('amount'))
                @php($totalOutstanding = $memberMemberships->sum(fn($historyMembership) => (float) $historyMembership->balance))
                <tr>
                    <td>
                        <strong>{{ $member->client?->name }}</strong>
                        <div class="small text-muted">{{ $member->member_number }} · {{ $member->client?->email ?: $member->client?->phone }}</div>
                        <div class="small text-muted text-break">QR: {{ $member->qr_code }}</div>
                    </td>
                    <td>{{ $active?->plan?->name ?? 'No active plan' }}<div class="small text-muted">{{ $active?->membership_number }} {{ $active?->ends_at ? '· ends '.$active->ends_at->format('d M Y') : '' }}</div></td>
                    <td>{{ $member->assignedTrainer?->name ?: 'Unassigned' }}</td>
                    <td><span class="status-pill">{{ $member->status }}</span></td>
                    <td>
                        <form method="post" action="{{ route('fitness.members.update', $member) }}" class="d-flex flex-wrap gap-1 mb-1">
                            @csrf @method('PATCH')
                            <select class="form-select form-select-sm" name="assigned_trainer_id" style="max-width:160px"><option value="">Trainer</option>@foreach($trainers as $trainer)<option value="{{ $trainer->id }}" @selected($member->assigned_trainer_id === $trainer->id)>{{ $trainer->name }}</option>@endforeach</select>
                            <select class="form-select form-select-sm" name="status" style="max-width:120px">@foreach($statuses as $status)<option @selected($member->status === $status)>{{ $status }}</option>@endforeach</select>
                            <input type="hidden" name="gender" value="{{ $member->gender }}">
                            <input type="hidden" name="date_of_birth" value="{{ $member->date_of_birth?->toDateString() }}">
                            <input type="hidden" name="address" value="{{ $member->address }}">
                            <input type="hidden" name="emergency_contact_name" value="{{ $member->emergency_contact_name }}">
                            <input type="hidden" name="emergency_contact_phone" value="{{ $member->emergency_contact_phone }}">
                            <input type="hidden" name="occupation" value="{{ $member->occupation }}">
                            <button class="btn btn-sm btn-outline-dark">Save</button>
                            <a class="btn btn-sm btn-warning" href="{{ route('fitness.members.card', $member) }}"><i class="bi bi-qr-code me-1"></i>Card</a>
                        </form>
                    </td>
                </tr>
                <tr class="fitness-history-row">
                    <td colspan="5">
                        <details class="fitness-history">
                            <summary>
                                <span><i class="bi bi-clock-history me-1"></i>Member History</span>
                                <span class="fitness-history-summary">
                                    <span>{{ $memberMemberships->count() }} memberships</span>
                                    <span>{{ $memberVisits->count() }} visits</span>
                                    <span>{{ $memberBookings->count() }} class bookings</span>
                                    <span>Paid {{ number_format($totalPaid, 2) }}</span>
                                    <span>Balance {{ number_format($totalOutstanding, 2) }}</span>
                                </span>
                            </summary>
                            <div class="fitness-history-grid">
                                <div class="fitness-history-panel">
                                    <h3>Membership Timeline</h3>
                                    @forelse($memberMemberships->take(5) as $historyMembership)
                                        <div class="fitness-history-item">
                                            <div class="fitness-history-line">
                                                <strong>{{ $historyMembership->plan?->name ?: 'Membership' }}</strong>
                                                <span class="status-pill">{{ $historyMembership->status }}</span>
                                            </div>
                                            <div class="fitness-history-note">{{ $historyMembership->membership_number }} · {{ $historyMembership->starts_at?->format('d M Y') ?: '-' }} to {{ $historyMembership->ends_at?->format('d M Y') ?: '-' }}</div>
                                            <div class="fitness-history-note">Credits {{ $historyMembership->session_credits_remaining ?? 'Unlimited' }} · Balance {{ number_format($historyMembership->balance, 2) }}</div>
                                            @foreach($historyMembership->events->sortByDesc('created_at')->take(2) as $event)
                                                <div class="fitness-history-note">{{ $event->created_at?->format('d M Y H:i') }} · {{ str_replace('.', ' ', $event->event) }}</div>
                                            @endforeach
                                            @foreach($historyMembership->freezes->sortByDesc('starts_at')->take(1) as $freeze)
                                                <div class="fitness-history-note">Freeze: {{ $freeze->starts_at?->format('d M Y') }} to {{ $freeze->ends_at?->format('d M Y') }} · {{ $freeze->reason }}</div>
                                            @endforeach
                                        </div>
                                    @empty
                                        <div class="text-muted">No membership history yet.</div>
                                    @endforelse
                                </div>

                                <div class="fitness-history-panel">
                                    <h3>Payments & Invoices</h3>
                                    @forelse($memberPayments->take(6) as $payment)
                                        <div class="fitness-history-item">
                                            <div class="fitness-history-line">
                                                <strong>{{ number_format($payment->amount, 2) }}</strong>
                                                <span>{{ $payment->payment_date?->format('d M Y') }}</span>
                                            </div>
                                            <div class="fitness-history-note">{{ $payment->paymentMethod?->name ?: 'Payment' }}{{ $payment->reference ? ' · '.$payment->reference : '' }}</div>
                                            @if($payment->invoice)
                                                <div class="fitness-history-note"><a href="{{ route('invoices.show', $payment->invoice) }}">{{ $payment->invoice->invoice_number }}</a> · Balance {{ number_format($payment->invoice->balance, 2) }}</div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="text-muted">No recorded payments yet.</div>
                                    @endforelse
                                </div>

                                <div class="fitness-history-panel">
                                    <h3>Attendance</h3>
                                    @forelse($memberVisits->take(6) as $visit)
                                        <div class="fitness-history-item">
                                            <div class="fitness-history-line">
                                                <strong>{{ \Illuminate\Support\Carbon::parse($visit->entry_time)->format('d M Y') }}</strong>
                                                <span>{{ $visit->status }}</span>
                                            </div>
                                            <div class="fitness-history-note">{{ \Illuminate\Support\Carbon::parse($visit->entry_time)->format('H:i') }}{{ $visit->exit_time ? ' - '.\Illuminate\Support\Carbon::parse($visit->exit_time)->format('H:i') : ' - still in gym' }}</div>
                                            <div class="fitness-history-note">{{ $visit->method }}{{ $visit->visit_minutes ? ' · '.$visit->visit_minutes.' min' : '' }}</div>
                                        </div>
                                    @empty
                                        <div class="text-muted">No attendance history yet.</div>
                                    @endforelse
                                </div>

                                <div class="fitness-history-panel">
                                    <h3>Classes</h3>
                                    @forelse($memberBookings->take(6) as $booking)
                                        <div class="fitness-history-item">
                                            <div class="fitness-history-line">
                                                <strong>{{ $booking->class_name }}</strong>
                                                <span>{{ $booking->status }}</span>
                                            </div>
                                            <div class="fitness-history-note">{{ \Illuminate\Support\Carbon::parse($booking->starts_at)->format('d M Y H:i') }}</div>
                                            @if($booking->attended_at)<div class="fitness-history-note">Attended {{ \Illuminate\Support\Carbon::parse($booking->attended_at)->format('d M Y H:i') }}</div>@endif
                                        </div>
                                    @empty
                                        <div class="text-muted">No class booking history yet.</div>
                                    @endforelse
                                </div>
                            </div>
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No members yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $members->links() }}</div>
</div>

<div class="card p-3">
    <h2 class="h5 mb-3">Recent Memberships</h2>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Membership</th><th>Period</th><th>Balance</th><th>Renew / Freeze / Pay</th></tr></thead>
            <tbody>
            @forelse($memberships as $membership)
                <tr>
                    <td>
                        <strong>{{ $membership->membership_number }}</strong>
                        <div class="small text-muted">{{ $membership->member?->client?->name }} · {{ $membership->plan?->name }}</div>
                        @if($membership->member)
                            <a class="btn btn-sm btn-outline-dark mt-1" href="{{ route('fitness.members.card', $membership->member) }}"><i class="bi bi-person-vcard me-1"></i>Membership Card</a>
                        @endif
                    </td>
                    <td>{{ $membership->starts_at?->format('d M Y') }} - {{ $membership->ends_at?->format('d M Y') }}<div><span class="status-pill">{{ $membership->status }}</span></div></td>
                    <td>{{ number_format($membership->balance, 2) }}</td>
                    <td>
                        <form method="post" action="{{ route('fitness.member-memberships.renew', $membership) }}" class="d-inline-flex gap-1 mb-1">@csrf<select class="form-select form-select-sm" name="fitness_membership_plan_id">@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected($membership->fitness_membership_plan_id === $plan->id)>{{ $plan->name }}</option>@endforeach</select><button class="btn btn-sm btn-outline-success">Renew</button></form>
                        <form method="post" action="{{ route('fitness.member-memberships.freeze', $membership) }}" class="d-inline-flex gap-1 mb-1">@csrf<input class="form-control form-control-sm" name="starts_at" type="date" required><input class="form-control form-control-sm" name="ends_at" type="date" required><input class="form-control form-control-sm" name="reason" placeholder="Reason" required><button class="btn btn-sm btn-outline-dark">Freeze</button></form>
                        <form method="post" action="{{ route('fitness.member-memberships.invoice', $membership) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-dark">Invoice</button></form>
                        <form method="post" action="{{ route('fitness.member-memberships.record-payment', $membership) }}" class="d-inline-flex gap-1 mt-1">@csrf<select class="form-select form-select-sm" name="payment_method_id"><option value="">Method</option>@foreach($paymentMethods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>@endforeach</select><input class="form-control form-control-sm" name="amount" type="number" min="0.01" step="0.01" placeholder="Amount" required><input class="form-control form-control-sm" name="payment_date" type="date" value="{{ now()->toDateString() }}" required><input class="form-control form-control-sm" name="reference" placeholder="Reference"><button class="btn btn-sm btn-warning">Record + Email Invoice</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">No memberships yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
