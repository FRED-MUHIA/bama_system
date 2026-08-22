@extends('layouts.app')
@section('title', 'Salon & Spa - '.$title)

@section('content')
<style>
    .salon-page{display:grid;gap:16px}
    .salon-head{background:#050806;color:#fff;border-radius:14px;padding:22px;border:1px solid rgba(0,166,81,.26)}
    .salon-kicker{color:#71f0ad;font-size:.76rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em}
    .salon-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
    .salon-card{background:#fff;border:1px solid #e7e9ee;border-radius:12px;padding:16px;box-shadow:0 12px 28px rgba(15,23,42,.05)}
    .salon-list{display:grid;gap:10px}
    .salon-item{display:flex;justify-content:space-between;gap:14px;border:1px solid #ecedf0;border-radius:10px;padding:12px;background:#fff}
    .salon-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:flex-end}
    .salon-pill{display:inline-flex;align-items:center;border-radius:999px;padding:.25rem .6rem;background:#e9fff2;color:#007a3b;font-size:.76rem;font-weight:800}
    .salon-form{display:grid;gap:10px}
    @media(max-width:900px){.salon-grid{grid-template-columns:1fr}.salon-item{flex-direction:column}.salon-actions{justify-content:flex-start}}
</style>

<div class="salon-page">
    <section class="salon-head">
        <div class="salon-kicker">Salon & Spa Workspace</div>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h1 class="mb-2">{{ $title }}</h1>
                <p class="mb-0 text-white-50">{{ $description }}</p>
            </div>
            <a class="btn btn-light rounded-pill fw-bold" href="{{ route('salon.dashboard') }}">Dashboard</a>
        </div>
    </section>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if(isset($appointmentPage))
        <section class="salon-grid">
            <div class="salon-card">
                <h2 class="h5">Book appointment</h2>
                <form class="salon-form" method="post" action="{{ route('salon.appointments.store') }}">
                    @csrf
                    <select name="salon_client_profile_id" class="form-select" required>
                        <option value="">Client</option>
                        @foreach($clients as $profile)<option value="{{ $profile->id }}">{{ $profile->client?->name }} · {{ $profile->client_code }}</option>@endforeach
                    </select>
                    <select name="salon_staff_profile_id" class="form-select">
                        <option value="">Primary staff</option>
                        @foreach($staff as $member)<option value="{{ $member->id }}">{{ $member->display_name }}</option>@endforeach
                    </select>
                    <select name="salon_resource_id" class="form-select">
                        <option value="">Chair / room</option>
                        @foreach($resources as $resource)<option value="{{ $resource->id }}">{{ $resource->name }} · {{ $resource->type }}</option>@endforeach
                    </select>
                    <input type="datetime-local" name="starts_at" class="form-control" required>
                    <select name="services[0][service_id]" class="form-select" required>
                        <option value="">Service</option>
                        @foreach($services as $service)<option value="{{ $service->id }}">{{ $service->name }} · {{ number_format((float) $service->price, 2) }}</option>@endforeach
                    </select>
                    <button class="btn btn-success rounded-pill fw-bold">Book appointment</button>
                </form>
            </div>
            <div class="salon-card">
                <h2 class="h5">Upcoming appointments</h2>
                <div class="salon-list">
                    @forelse($appointments as $appointment)
                        <div class="salon-item">
                            <div>
                                <strong>{{ $appointment->profile?->client?->name ?? 'Walk-in client' }}</strong>
                                <div class="small text-muted">{{ $appointment->appointment_number }} · {{ $appointment->starts_at?->format('d M H:i') }}</div>
                            </div>
                            <form method="post" action="{{ route('salon.appointments.complete', $appointment) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-success">Complete</button>
                            </form>
                        </div>
                    @empty
                        <div class="text-muted">No appointments scheduled.</div>
                    @endforelse
                </div>
            </div>
        </section>
    @endif

    @if(isset($clients) && !isset($appointments))
        <section class="salon-grid">
            <div class="salon-card">
                <h2 class="h5">Create client profile</h2>
                <form class="salon-form" method="post" action="{{ route('salon.clients.store') }}">
                    @csrf
                    <input name="name" class="form-control" placeholder="Client name" required>
                    <input name="phone" class="form-control" placeholder="Phone">
                    <input name="email" type="email" class="form-control" placeholder="Email">
                    <input name="date_of_birth" type="date" class="form-control">
                    <button class="btn btn-success rounded-pill fw-bold">Create profile</button>
                </form>
            </div>
            <div class="salon-card">
                <h2 class="h5">Client profiles</h2>
                <div class="salon-list">
                    @foreach($clients as $profile)
                        <div class="salon-item">
                            <div><strong>{{ $profile->client?->name }}</strong><div class="small text-muted">{{ $profile->client_code }} · {{ $profile->loyalty_tier }}</div></div>
                            <span>{{ number_format((float) $profile->lifetime_spend, 2) }}</span>
                        </div>
                    @endforeach
                </div>
                @if(method_exists($clients, 'links'))<div class="mt-3">{{ $clients->links() }}</div>@endif
            </div>
        </section>
    @endif

    @if(isset($staff) && !isset($appointmentPage))
        <section class="salon-grid">
            <div class="salon-card">
                <h2 class="h5">Add staff</h2>
                <form class="salon-form" method="post" action="{{ route('salon.staff.store') }}">
                    @csrf
                    <input name="display_name" class="form-control" placeholder="Display name" required>
                    <input name="role_title" class="form-control" placeholder="Role title">
                    <input name="commission_rate" type="number" step="0.01" class="form-control" placeholder="Commission %">
                    <button class="btn btn-success rounded-pill fw-bold">Add staff</button>
                </form>
            </div>
            <div class="salon-card">
                <h2 class="h5">Staff profiles</h2>
                <div class="salon-list">
                    @foreach($staff as $member)
                        <div class="salon-item">
                            <div><strong>{{ $member->display_name }}</strong><div class="small text-muted">{{ $member->role_title ?: 'Stylist / Therapist' }}</div></div>
                            <span class="salon-pill">{{ $member->status }}</span>
                        </div>
                    @endforeach
                </div>
                @if(method_exists($staff, 'links'))<div class="mt-3">{{ $staff->links() }}</div>@endif
            </div>
        </section>
    @endif

    @if(isset($services) && !isset($appointments))
        <section class="salon-grid">
            <div class="salon-card">
                <h2 class="h5">Create service</h2>
                <form class="salon-form" method="post" action="{{ route('salon.services.store') }}">
                    @csrf
                    <input name="name" class="form-control" placeholder="Service name" required>
                    <input name="category" class="form-control" placeholder="Category">
                    <div class="row g-2">
                        <div class="col-6">
                            <input name="duration_hours" type="number" min="0" max="24" class="form-control" placeholder="Hours" value="{{ old('duration_hours', 0) }}">
                        </div>
                        <div class="col-6">
                            <input name="duration_minutes_part" type="number" min="0" max="59" step="5" class="form-control" placeholder="Minutes" value="{{ old('duration_minutes_part', 30) }}">
                        </div>
                    </div>
                    <input name="price" type="number" step="0.01" class="form-control" placeholder="Price" required>
                    <input name="commission_rate" type="number" step="0.01" class="form-control" placeholder="Commission %">
                    <button class="btn btn-success rounded-pill fw-bold">Create service</button>
                </form>
            </div>
            <div class="salon-card">
                <h2 class="h5">Services</h2>
                <div class="salon-list">
                    @foreach($services as $service)
                        @php
                            $hours = intdiv((int) $service->duration_minutes, 60);
                            $minutes = (int) $service->duration_minutes % 60;
                            $duration = trim(($hours ? $hours.'h ' : '').($minutes ? $minutes.'m' : '')) ?: '0m';
                        @endphp
                        <div class="salon-item">
                            <div><strong>{{ $service->name }}</strong><div class="small text-muted">{{ $service->category ?: 'General' }} · {{ $duration }}</div></div>
                            <strong>{{ number_format((float) $service->price, 2) }}</strong>
                        </div>
                    @endforeach
                </div>
                @if(method_exists($services, 'links'))<div class="mt-3">{{ $services->links() }}</div>@endif
            </div>
        </section>
    @endif

    @if(isset($membershipClients))
        <section class="salon-grid">
            <div class="salon-card">
                <h2 class="h5">Create membership plan</h2>
                <form class="salon-form" method="post" action="{{ route('salon.membership-plans.store') }}">
                    @csrf
                    <input name="name" class="form-control" placeholder="Plan name" required>
                    <select name="billing_cycle" class="form-select" required>
                        <option value="Monthly">Monthly</option>
                        <option value="Weekly">Weekly</option>
                        <option value="Quarterly">Quarterly</option>
                        <option value="Annual">Annual</option>
                    </select>
                    <input name="price" type="number" step="0.01" min="0" class="form-control" placeholder="Plan price" required>
                    <input name="visit_allowance" type="number" min="0" class="form-control" placeholder="Visit allowance">
                    <input name="discount_rate" type="number" step="0.01" min="0" max="100" class="form-control" placeholder="Discount %">
                    <textarea name="benefits" class="form-control" rows="3" placeholder="Benefits, one per line"></textarea>
                    <button class="btn btn-success rounded-pill fw-bold">Create plan</button>
                </form>
            </div>

            <div class="salon-card">
                <h2 class="h5">Enroll member</h2>
                <form class="salon-form" method="post" action="{{ route('salon.memberships.store') }}">
                    @csrf
                    <select name="salon_client_profile_id" class="form-select" required>
                        <option value="">Choose client profile</option>
                        @foreach($membershipClients as $profile)
                            <option value="{{ $profile->id }}">{{ $profile->client?->name }} · {{ $profile->client_code }} · {{ $profile->loyaltyAccount?->points_balance ?? 0 }} pts</option>
                        @endforeach
                    </select>
                    <select name="salon_membership_plan_id" class="form-select" required>
                        <option value="">Choose membership plan</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} · {{ $plan->billing_cycle }} · {{ number_format((float) $plan->price, 2) }}</option>
                        @endforeach
                    </select>
                    <div class="row g-2">
                        <div class="col-6"><input name="starts_on" type="date" class="form-control" value="{{ now()->toDateString() }}"></div>
                        <div class="col-6"><input name="ends_on" type="date" class="form-control" placeholder="Ends on"></div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6"><input name="visits_remaining" type="number" min="0" class="form-control" placeholder="Visits"></div>
                        <div class="col-6"><input name="bonus_points" type="number" min="0" class="form-control" placeholder="Join bonus points"></div>
                    </div>
                    <button class="btn btn-success rounded-pill fw-bold">Enroll member</button>
                </form>
            </div>
        </section>
    @endif

    @if(isset($consultationClients))
        <section class="salon-grid">
            <div class="salon-card">
                <h2 class="h5">Add consultation</h2>
                <form class="salon-form" method="post" action="{{ route('salon.consultations.store') }}">
                    @csrf
                    <select name="salon_client_profile_id" class="form-select" required>
                        <option value="">Client profile</option>
                        @foreach($consultationClients as $profile)
                            <option value="{{ $profile->id }}">{{ $profile->client?->name }} · {{ $profile->client_code }}</option>
                        @endforeach
                    </select>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <select name="salon_staff_profile_id" class="form-select">
                                <option value="">Consultant / staff</option>
                                @foreach($consultationStaff as $member)<option value="{{ $member->id }}">{{ $member->display_name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input name="consultation_type" class="form-control" placeholder="Consultation type" value="{{ old('consultation_type', 'Beauty') }}">
                        </div>
                    </div>
                    <select name="salon_appointment_id" class="form-select">
                        <option value="">Link appointment optional</option>
                        @foreach($consultationAppointments as $appointment)
                            <option value="{{ $appointment->id }}">{{ $appointment->appointment_number }} · {{ $appointment->profile?->client?->name ?? 'Walk-in' }}</option>
                        @endforeach
                    </select>
                    <textarea name="observations" class="form-control" rows="3" placeholder="Observations, one per line"></textarea>
                    <textarea name="recommendations" class="form-control" rows="3" placeholder="Recommendations, one per line"></textarea>
                    <textarea name="contraindications" class="form-control" rows="2" placeholder="Contraindications, one per line"></textarea>
                    <input name="follow_up_date" type="date" class="form-control">
                    <button class="btn btn-success rounded-pill fw-bold">Add consultation</button>
                </form>
            </div>

            <div class="salon-card">
                <h2 class="h5">How to use consultations</h2>
                <div class="salon-list">
                    <div class="salon-item"><strong>Select the client</strong><span class="salon-pill">Required</span></div>
                    <div class="salon-item"><strong>Add observations and risks</strong><span class="salon-pill">Clinical notes</span></div>
                    <div class="salon-item"><strong>Save recommendations</strong><span class="salon-pill">Follow-up ready</span></div>
                </div>
            </div>
        </section>
    @endif

    @if(isset($treatmentClients))
        <section class="salon-grid">
            <div class="salon-card">
                <h2 class="h5">Add treatment</h2>
                <form class="salon-form" method="post" action="{{ route('salon.treatments.store') }}">
                    @csrf
                    <select name="salon_client_profile_id" class="form-select" required>
                        <option value="">Client profile</option>
                        @foreach($treatmentClients as $profile)
                            <option value="{{ $profile->id }}">{{ $profile->client?->name }} · {{ $profile->client_code }}</option>
                        @endforeach
                    </select>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <input name="name" class="form-control" placeholder="Treatment name" required>
                        </div>
                        <div class="col-md-6">
                            <input name="performed_on" type="date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <select name="salon_service_id" class="form-select">
                                <option value="">Related service</option>
                                @foreach($treatmentServices as $service)<option value="{{ $service->id }}">{{ $service->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select name="salon_staff_profile_id" class="form-select">
                                <option value="">Staff member</option>
                                @foreach($treatmentStaff as $member)<option value="{{ $member->id }}">{{ $member->display_name }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <select name="salon_appointment_id" class="form-select">
                        <option value="">Link appointment optional</option>
                        @foreach($treatmentAppointments as $appointment)
                            <option value="{{ $appointment->id }}">{{ $appointment->appointment_number }} · {{ $appointment->profile?->client?->name ?? 'Walk-in' }}</option>
                        @endforeach
                    </select>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Treatment notes"></textarea>
                    <textarea name="products_used" class="form-control" rows="2" placeholder="Products used, one per line"></textarea>
                    <textarea name="aftercare" class="form-control" rows="3" placeholder="Aftercare instructions, one per line"></textarea>
                    <button class="btn btn-success rounded-pill fw-bold">Add treatment</button>
                </form>
            </div>

            <div class="salon-card">
                <h2 class="h5">Treatment workflow</h2>
                <div class="salon-list">
                    <div class="salon-item"><strong>Choose client and service</strong><span class="salon-pill">Required</span></div>
                    <div class="salon-item"><strong>Record products used</strong><span class="salon-pill">Inventory ready</span></div>
                    <div class="salon-item"><strong>Add aftercare</strong><span class="salon-pill">Client history</span></div>
                </div>
            </div>
        </section>
    @endif

    @if(isset($loyaltyClients))
        <section class="salon-grid">
            <div class="salon-card">
                <h2 class="h5">Award loyalty points</h2>
                <form class="salon-form" method="post" action="#" data-action-template="{{ route('salon.loyalty.points.store', ['profile' => '__PROFILE__']) }}" onsubmit="this.action=this.dataset.actionTemplate.replace('__PROFILE__', this.querySelector('[name=profile_id]').value)">
                    @csrf
                    <select name="profile_id" class="form-select" required>
                        <option value="">Client profile</option>
                        @foreach($loyaltyClients as $profile)
                            <option value="{{ $profile->id }}">{{ $profile->client?->name }} · {{ $profile->loyaltyAccount?->points_balance ?? 0 }} pts</option>
                        @endforeach
                    </select>
                    <div class="row g-2">
                        <div class="col-md-4"><input name="points" type="number" min="1" class="form-control" placeholder="Points" required></div>
                        <div class="col-md-8"><input name="reason" class="form-control" placeholder="Reason"></div>
                    </div>
                    <button class="btn btn-success rounded-pill fw-bold">Award points</button>
                </form>
            </div>

            <div class="salon-card">
                <h2 class="h5">Issue gift card</h2>
                <form class="salon-form" method="post" action="{{ route('salon.gift-cards.store') }}">
                    @csrf
                    <select name="client_id" class="form-select">
                        <option value="">Walk-in / unassigned</option>
                        @foreach($giftCardClients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach
                    </select>
                    <div class="row g-2">
                        <div class="col-md-5"><input name="amount" type="number" step="0.01" min="1" class="form-control" placeholder="Amount" required></div>
                        <div class="col-md-3"><input name="currency" maxlength="3" class="form-control" value="KES" placeholder="KES"></div>
                        <div class="col-md-4"><input name="expires_on" type="date" class="form-control"></div>
                    </div>
                    <button class="btn btn-success rounded-pill fw-bold">Issue gift card</button>
                </form>
            </div>
        </section>
    @endif

    @if(isset($inventoryServices))
        <section class="salon-grid">
            <div class="salon-card">
                <h2 class="h5">Record product usage</h2>
                <form class="salon-form" method="post" action="{{ route('salon.inventory.consumption.quick-store') }}">
                    @csrf
                    <select name="salon_appointment_id" class="form-select" required>
                        <option value="">Appointment</option>
                        @foreach($appointments as $appointment)
                            <option value="{{ $appointment->id }}">{{ $appointment->appointment_number }} · {{ $appointment->profile?->client?->name ?? 'Walk-in' }}</option>
                        @endforeach
                    </select>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <select name="product_id" class="form-select" required>
                                <option value="">Product</option>
                                @foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }} · Stock {{ $product->stock_quantity ?? 0 }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select name="salon_service_id" class="form-select">
                                <option value="">Related service</option>
                                @foreach($inventoryServices as $service)<option value="{{ $service->id }}">{{ $service->name }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4"><input name="quantity" type="number" step="0.001" min="0.001" class="form-control" placeholder="Qty" required></div>
                        <div class="col-md-4"><input name="unit" class="form-control" placeholder="Unit" value="pcs"></div>
                        <div class="col-md-4"><input name="unit_cost" type="number" step="0.01" min="0" class="form-control" placeholder="Unit cost"></div>
                    </div>
                    <button class="btn btn-success rounded-pill fw-bold">Record usage</button>
                </form>
            </div>

            <div class="salon-card">
                <h2 class="h5">Usage controls</h2>
                <div class="salon-list">
                    <div class="salon-item"><strong>Deducts stock</strong><span class="salon-pill">Inventory</span></div>
                    <div class="salon-item"><strong>Links appointment</strong><span class="salon-pill">Cost trace</span></div>
                    <div class="salon-item"><strong>Tracks service usage</strong><span class="salon-pill">Reporting</span></div>
                </div>
            </div>
        </section>
    @endif

    @if(isset($commissionStaff))
        <section class="salon-grid">
            <div class="salon-card">
                <h2 class="h5">Record commission</h2>
                <form class="salon-form" method="post" action="{{ route('salon.commissions.store') }}">
                    @csrf
                    <select name="salon_staff_profile_id" class="form-select" required>
                        <option value="">Staff member</option>
                        @foreach($commissionStaff as $member)<option value="{{ $member->id }}">{{ $member->display_name }}</option>@endforeach
                    </select>
                    <select name="salon_appointment_id" class="form-select">
                        <option value="">Appointment optional</option>
                        @foreach($commissionAppointments as $appointment)<option value="{{ $appointment->id }}">{{ $appointment->appointment_number }}</option>@endforeach
                    </select>
                    <div class="row g-2">
                        <div class="col-md-4"><input name="commission_date" type="date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                        <div class="col-md-4"><input name="base_amount" type="number" step="0.01" min="0" class="form-control" placeholder="Base amount" required></div>
                        <div class="col-md-4"><input name="rate" type="number" step="0.01" min="0" max="100" class="form-control" placeholder="Rate %" required></div>
                    </div>
                    <select name="status" class="form-select">
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Paid">Paid</option>
                        <option value="Void">Void</option>
                    </select>
                    <button class="btn btn-success rounded-pill fw-bold">Record commission</button>
                </form>
            </div>

            <div class="salon-card">
                <h2 class="h5">Commission actions</h2>
                <div class="salon-list">
                    <div class="salon-item"><strong>Pending</strong><span class="salon-pill">New accruals</span></div>
                    <div class="salon-item"><strong>Approved</strong><span class="salon-pill">Ready for payroll</span></div>
                    <div class="salon-item"><strong>Paid / Void</strong><span class="salon-pill">Closed</span></div>
                </div>
            </div>
        </section>
    @endif

    @if(isset($wellnessClients))
        <section class="salon-grid">
            <div class="salon-card">
                <h2 class="h5">Create wellness program</h2>
                <form class="salon-form" method="post" action="{{ route('salon.wellness.programs.store') }}">
                    @csrf
                    <input name="name" class="form-control" placeholder="Program name" required>
                    <input name="category" class="form-control" placeholder="Category" value="Wellness">
                    <textarea name="description" class="form-control" rows="2" placeholder="Description"></textarea>
                    <div class="row g-2">
                        <div class="col-md-6"><input name="duration_days" type="number" min="1" class="form-control" value="30" placeholder="Duration days" required></div>
                        <div class="col-md-6"><input name="price" type="number" step="0.01" min="0" class="form-control" placeholder="Price" required></div>
                    </div>
                    <textarea name="milestones" class="form-control" rows="3" placeholder="Milestones, one per line"></textarea>
                    <button class="btn btn-success rounded-pill fw-bold">Create program</button>
                </form>
            </div>

            <div class="salon-card">
                <h2 class="h5">Enroll client</h2>
                <form class="salon-form" method="post" action="{{ route('salon.wellness.enrollments.store') }}">
                    @csrf
                    <select name="salon_client_profile_id" class="form-select" required>
                        <option value="">Client profile</option>
                        @foreach($wellnessClients as $profile)<option value="{{ $profile->id }}">{{ $profile->client?->name }} · {{ $profile->client_code }}</option>@endforeach
                    </select>
                    <select name="salon_wellness_program_id" class="form-select" required>
                        <option value="">Wellness program</option>
                        @foreach($programs as $program)<option value="{{ $program->id }}">{{ $program->name }} · {{ $program->duration_days }} days</option>@endforeach
                    </select>
                    <div class="row g-2">
                        <div class="col-md-6"><input name="starts_on" type="date" class="form-control" value="{{ now()->toDateString() }}"></div>
                        <div class="col-md-6"><input name="ends_on" type="date" class="form-control"></div>
                    </div>
                    <textarea name="progress" class="form-control" rows="3" placeholder="Initial progress notes, one per line"></textarea>
                    <button class="btn btn-success rounded-pill fw-bold">Enroll client</button>
                </form>
            </div>
        </section>
    @endif

    <section class="salon-grid">
        @foreach([
            'plans' => 'Membership Plans',
            'memberships' => 'Memberships',
            'loyalty' => 'Loyalty Accounts',
            'giftCards' => 'Gift Cards',
            'consultations' => 'Consultations',
            'treatments' => 'Treatments',
            'consumptions' => 'Product Consumption',
            'commissions' => 'Commissions',
            'programs' => 'Wellness Programs',
            'enrollments' => 'Wellness Enrollments',
        ] as $var => $label)
            @if(isset($$var))
                <div class="salon-card">
                    <h2 class="h5">{{ $label }}</h2>
                    <div class="salon-list">
                        @forelse($$var as $row)
                            <div class="salon-item">
                                <div>
                                    <strong>{{ $row->name ?? $row->membership_number ?? $row->card_number ?? $row->profile?->client?->name ?? $row->staff?->display_name ?? $row->appointment?->appointment_number ?? 'Record #'.$row->id }}</strong>
                                    <div class="small text-muted">
                                        @if($var === 'memberships')
                                            {{ $row->profile?->client?->name }} · {{ $row->plan?->name }} · {{ $row->starts_on?->format('d M Y') }} - {{ $row->ends_on?->format('d M Y') }}
                                        @elseif($var === 'consultations')
                                            {{ $row->profile?->client?->name }} · {{ $row->consultation_type }} · Follow-up {{ $row->follow_up_date?->format('d M Y') ?? 'not set' }}
                                        @elseif($var === 'treatments')
                                            {{ $row->profile?->client?->name }} · {{ $row->service?->name ?? 'No service linked' }} · {{ $row->performed_on?->format('d M Y') }}
                                        @elseif($var === 'loyalty')
                                            {{ $row->profile?->client?->name }} · {{ $row->tier }} · Lifetime {{ number_format((float) $row->lifetime_points) }} pts
                                        @elseif($var === 'giftCards')
                                            {{ $row->client?->name ?? 'Unassigned' }} · {{ $row->currency }} · Expires {{ $row->expires_on?->format('d M Y') ?? 'not set' }}
                                        @elseif($var === 'consumptions')
                                            {{ $row->appointment?->appointment_number ?? 'No appointment' }} · {{ $row->product?->name }} · {{ number_format((float) $row->quantity, 3) }} {{ $row->unit }}
                                        @elseif($var === 'commissions')
                                            {{ $row->staff?->display_name }} · {{ $row->commission_date?->format('d M Y') }} · {{ $row->rate }}%
                                        @elseif($var === 'programs')
                                            {{ $row->category }} · {{ $row->duration_days }} days · {{ $row->enrollments_count ?? 0 }} enrolled
                                        @elseif($var === 'enrollments')
                                            {{ $row->profile?->client?->name }} · {{ $row->program?->name }} · {{ $row->starts_on?->format('d M Y') }} - {{ $row->ends_on?->format('d M Y') }}
                                        @else
                                            {{ $row->status ?? $row->tier ?? $row->category ?? $row->created_at?->format('d M Y') }}
                                        @endif
                                    </div>
                                </div>
                                @if($var === 'memberships')
                                    <form method="post" action="{{ route('salon.memberships.points.store', $row) }}" class="salon-actions">
                                        @csrf
                                        <input name="points" type="number" min="1" class="form-control form-control-sm" placeholder="Points" style="width:96px">
                                        <input name="reason" class="form-control form-control-sm" placeholder="Reason" style="width:130px">
                                        <button class="btn btn-sm btn-outline-success">Award</button>
                                    </form>
                                @elseif($var === 'commissions')
                                    <form method="post" action="{{ route('salon.commissions.status', $row) }}" class="salon-actions">
                                        @csrf
                                        <select name="status" class="form-select form-select-sm" style="width:118px">
                                            @foreach(['Pending', 'Approved', 'Paid', 'Void'] as $status)
                                                <option value="{{ $status }}" @selected($row->status === $status)>{{ $status }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-outline-success">Update</button>
                                    </form>
                                @else
                                    <span class="salon-pill">{{ $row->points_balance ?? $row->balance ?? $row->amount ?? $row->price ?? $row->total_cost ?? 'Ready' }}</span>
                                @endif
                            </div>
                        @empty
                            <div class="text-muted">No {{ strtolower($label) }} yet.</div>
                        @endforelse
                    </div>
                    @if(method_exists($$var, 'links'))<div class="mt-3">{{ $$var->links() }}</div>@endif
                </div>
            @endif
        @endforeach

        @if(isset($reports))
            <div class="salon-card">
                <h2 class="h5">Report catalogue</h2>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($reports as $report)
                        <span class="salon-pill">{{ $report }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if(isset($metrics))
            <div class="salon-card">
                <h2 class="h5">Metrics</h2>
                <div class="salon-list">
                    @foreach($metrics as $label => $value)
                        <div class="salon-item"><strong>{{ $label }}</strong><span>{{ is_numeric($value) ? number_format((float) $value, 2) : $value }}</span></div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
</div>
@endsection
