@extends('layouts.app')
@section('title', 'Fitness Memberships')

@section('content')
@include('fitness.partials.nav')

<div class="card p-3 mb-3">
    <h2 class="h5 mb-3">Create Membership Plan</h2>
    <form method="post" action="{{ route('fitness.membership-plans.store') }}" class="row g-2">
        @csrf
        <div class="col-md-3"><input class="form-control" name="name" placeholder="Plan name" required></div>
        <div class="col-md-2"><select class="form-select" name="plan_type" required>@foreach($planTypes as $type)<option>{{ $type }}</option>@endforeach</select></div>
        <div class="col-md-1"><input class="form-control" name="currency" maxlength="3" value="KES" required></div>
        <div class="col-md-2"><input class="form-control" name="price" type="number" step="0.01" min="0" placeholder="Price" required></div>
        <div class="col-md-2"><input class="form-control" name="duration_days" type="number" min="1" value="30" placeholder="Days" required></div>
        <div class="col-md-2"><select class="form-select" name="status">@foreach($statuses as $status)<option>{{ $status }}</option>@endforeach</select></div>
        <div class="col-md-2"><input class="form-control" name="joining_fee" type="number" step="0.01" min="0" placeholder="Joining fee"></div>
        <div class="col-md-2"><input class="form-control" name="renewal_fee" type="number" step="0.01" min="0" placeholder="Renewal fee"></div>
        <div class="col-md-2"><input class="form-control" name="session_credits" type="number" min="0" placeholder="Credits blank = unlimited"></div>
        <div class="col-md-2"><input class="form-control" name="guest_passes" type="number" min="0" placeholder="Guest passes"></div>
        <div class="col-md-2"><label class="form-check pt-2"><input class="form-check-input" type="checkbox" name="freeze_allowed" value="1"> Freeze allowed</label></div>
        <div class="col-md-10"><input class="form-control" name="description" placeholder="Description"></div>
        <div class="col-md-2"><button class="btn btn-warning w-100">Save Plan</button></div>
    </form>
</div>

<div class="card mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="h5 mb-0">Issued Membership Cards / IDs</h2>
            <div class="small text-muted">Use the membership ID, member ID, or QR scan code at check-in.</div>
        </div>
        <a class="btn btn-sm btn-outline-dark" href="{{ route('fitness.members.index') }}"><i class="bi bi-person-plus me-1"></i>Enroll Member</a>
    </div>
    <div class="alert alert-warning m-3 mb-0">
        <strong>Payment recording:</strong> after money has been received, enter the amount, method, date, and reference. The system records the payment, updates the client invoice, emails the invoice PDF, and opens it for printing or sharing.
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Member</th><th>Membership ID</th><th>Member ID</th><th>QR Code</th><th>Status</th><th>Record Payment / Invoice</th><th>Card</th></tr></thead>
            <tbody>
            @forelse($issuedMemberships as $membership)
                @php($outstanding = max((float) $membership->balance, 0))
                <tr>
                    <td>
                        <strong>{{ $membership->member?->client?->name }}</strong>
                        <div class="small text-muted">{{ $membership->plan?->name }} · {{ $membership->member?->assignedTrainer?->name ?: 'No trainer' }}</div>
                    </td>
                    <td><strong>{{ $membership->membership_number }}</strong><div class="small text-muted">Expires {{ $membership->ends_at?->format('d M Y') ?: '-' }}</div></td>
                    <td><strong>{{ $membership->member?->member_number }}</strong></td>
                    <td>
                        @if($membership->member?->qr_code)
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ $membershipQrCodes[$membership->id] ?? '' }}" alt="QR code" style="width:72px;height:72px">
                                <code class="small text-break">{{ $membership->member->qr_code }}</code>
                            </div>
                        @else
                            <span class="text-muted">No QR code</span>
                        @endif
                    </td>
                    <td><span class="status-pill">{{ $membership->status }}</span></td>
                    <td style="min-width:360px">
                        <div class="small text-muted mb-1">
                            Outstanding: <strong>{{ number_format($outstanding, 2) }}</strong>
                            @if($membership->invoice)
                                · <a href="{{ route('invoices.show', $membership->invoice) }}">{{ $membership->invoice->invoice_number }}</a>
                            @endif
                        </div>
                        @if($outstanding > 0)
                            <form method="post" action="{{ route('fitness.member-memberships.record-payment', $membership) }}" class="d-flex flex-wrap gap-1">
                                @csrf
                                <input class="form-control form-control-sm" name="amount" type="number" min="0.01" max="{{ $outstanding }}" step="0.01" value="{{ number_format($outstanding, 2, '.', '') }}" style="max-width:110px" required>
                                <select class="form-select form-select-sm" name="payment_method_id" style="max-width:130px">
                                    <option value="">Method</option>
                                    @foreach($paymentMethods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>@endforeach
                                </select>
                                <input class="form-control form-control-sm" name="payment_date" type="date" value="{{ now()->toDateString() }}" style="max-width:135px" required>
                                <input class="form-control form-control-sm" name="reference" placeholder="Reference" style="max-width:130px">
                                <button class="btn btn-sm btn-warning"><i class="bi bi-send-check me-1"></i>Record + Email Invoice</button>
                            </form>
                        @else
                            @if($membership->invoice)
                                <a class="btn btn-sm btn-outline-dark" href="{{ route('invoices.show', $membership->invoice) }}"><i class="bi bi-file-earmark-text me-1"></i>Open Invoice</a>
                            @else
                                <form method="post" action="{{ route('fitness.member-memberships.invoice', $membership) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-dark"><i class="bi bi-file-earmark-plus me-1"></i>Generate Invoice</button></form>
                            @endif
                        @endif
                    </td>
                    <td>
                        @if($membership->member)
                            <a class="btn btn-sm btn-warning" href="{{ route('fitness.members.card', $membership->member) }}"><i class="bi bi-person-vcard me-1"></i>Open Card</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted">No issued memberships yet. Enroll a member to generate a membership ID and QR card.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Plan</th><th>Price</th><th>Terms</th><th>Status</th><th>Update</th></tr></thead>
            <tbody>
            @forelse($plans as $plan)
                <tr>
                    <td><strong>{{ $plan->name }}</strong><div class="small text-muted">{{ $plan->plan_type }} · {{ $plan->description }}</div></td>
                    <td>{{ $plan->currency }} {{ number_format($plan->price, 2) }}<div class="small text-muted">Join {{ number_format($plan->joining_fee, 2) }} · Renew {{ number_format($plan->renewal_fee, 2) }}</div></td>
                    <td>{{ $plan->duration_days }} days<div class="small text-muted">{{ $plan->session_credits === null ? 'Unlimited credits' : $plan->session_credits.' credits' }} · {{ $plan->guest_passes }} passes</div></td>
                    <td><span class="status-pill">{{ $plan->status }}</span></td>
                    <td>
                        <form method="post" action="{{ route('fitness.membership-plans.update', $plan) }}" class="d-flex flex-wrap gap-1">
                            @csrf @method('PATCH')
                            <input type="hidden" name="name" value="{{ $plan->name }}">
                            <input type="hidden" name="plan_type" value="{{ $plan->plan_type }}">
                            <input type="hidden" name="currency" value="{{ $plan->currency }}">
                            <input class="form-control form-control-sm" name="price" type="number" step="0.01" min="0" value="{{ $plan->price }}" style="max-width:110px">
                            <input class="form-control form-control-sm" name="duration_days" type="number" min="1" value="{{ $plan->duration_days }}" style="max-width:95px">
                            <input type="hidden" name="joining_fee" value="{{ $plan->joining_fee }}">
                            <input type="hidden" name="renewal_fee" value="{{ $plan->renewal_fee }}">
                            <input type="hidden" name="session_credits" value="{{ $plan->session_credits }}">
                            <input type="hidden" name="guest_passes" value="{{ $plan->guest_passes }}">
                            <input type="hidden" name="description" value="{{ $plan->description }}">
                            <select class="form-select form-select-sm" name="status" style="max-width:110px">@foreach($statuses as $status)<option @selected($plan->status === $status)>{{ $status }}</option>@endforeach</select>
                            @if($plan->freeze_allowed)<input type="hidden" name="freeze_allowed" value="1">@endif
                            <button class="btn btn-sm btn-outline-dark">Save</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No membership plans yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $plans->links() }}</div>
</div>
@endsection
