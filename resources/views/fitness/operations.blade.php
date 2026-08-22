@php
    $titles = [
        'trainers' => 'Trainers',
        'attendance' => 'Attendance',
        'check-in' => 'Check-In',
        'classes' => 'Class Scheduling',
        'programs' => 'Fitness Programs',
        'exercises' => 'Exercise Library',
        'health-profiles' => 'Health Profiles',
        'assessments' => 'Assessments',
        'personal-training' => 'Personal Training',
        'nutrition' => 'Nutrition',
        'challenges' => 'Challenges',
        'equipment' => 'Equipment',
        'reports' => 'Fitness Reports',
    ];
    $title = $titles[$section] ?? 'Fitness & Gym';
@endphp

@extends('layouts.app')
@section('title', $title)

@section('content')
@include('fitness.partials.nav')
<style>
    .fitness-form-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
    .fitness-two{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
    .fitness-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .fitness-metric{background:#fffdfa;border:1px solid #dedbd5;border-radius:8px;padding:14px}
    .fitness-metric .label{font-size:.72rem;text-transform:uppercase;font-weight:800;color:#667085;letter-spacing:.04em}
    .fitness-metric .value{font-size:1.45rem;font-weight:900;color:#00A651}
    .fitness-form-grid .span-2{grid-column:span 2}
    .fitness-form-grid .span-4{grid-column:span 4}
    @media(max-width:1100px){.fitness-form-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.fitness-two,.fitness-metrics{grid-template-columns:1fr}.fitness-form-grid .span-4{grid-column:span 2}}
    @media(max-width:640px){.fitness-form-grid{grid-template-columns:1fr}.fitness-form-grid .span-2,.fitness-form-grid .span-4{grid-column:span 1}}
</style>

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h4 mb-1">{{ $title }}</h2>
        <div class="text-muted small">Fitness operations are scoped to the active business and reuse the platform shared modules.</div>
    </div>
    @if($section === 'attendance')
        <a class="btn btn-sm btn-outline-dark" href="{{ route('fitness.attendance.export') }}"><i class="bi bi-download me-1"></i>CSV</a>
    @endif
</div>

@switch($section)
    @case('trainers')
        <div class="card p-3 mb-3">
            <h3 class="h6 mb-3">Trainer Profile</h3>
            <form method="post" action="{{ route('fitness.trainers.store') }}" class="fitness-form-grid">
                @csrf
                <select class="form-select" name="user_id" required>
                    <option value="">Staff user</option>
                    @foreach($staffUsers as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
                </select>
                <input class="form-control" name="trainer_code" placeholder="Trainer ID">
                <input class="form-control" name="specialization" placeholder="Specialization">
                <input class="form-control" name="hourly_rate" type="number" step="0.01" min="0" placeholder="Hourly rate">
                <input class="form-control" name="commission_percent" type="number" step="0.01" min="0" max="100" placeholder="Commission %">
                <input class="form-control" name="experience_years" type="number" min="0" placeholder="Experience years">
                <input class="form-control" name="max_clients" type="number" min="0" placeholder="Max clients">
                <input class="form-control" name="rating" type="number" step="0.01" min="0" max="5" placeholder="Rating">
                <select class="form-select" name="status"><option>Active</option><option>Inactive</option><option>Suspended</option></select>
                <textarea class="form-control span-2" name="certifications" rows="2" placeholder="Certifications, one per line"></textarea>
                <textarea class="form-control span-2" name="availability" rows="2" placeholder="Weekly availability, one line per day"></textarea>
                <textarea class="form-control span-4" name="bio" rows="2" placeholder="Bio"></textarea>
                <button class="btn btn-warning span-4">Save Trainer</button>
            </form>
        </div>
        <div class="card p-3">
            <h3 class="h6 mb-3">Trainer Directory</h3>
            <div class="table-responsive"><table class="table align-middle">
                <thead><tr><th>ID</th><th>Name</th><th>Specialization</th><th>Rate</th><th>Commission</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($trainerProfiles as $trainer)
                        <tr><td>{{ $trainer->trainer_code }}</td><td>{{ $trainer->trainer_name }}<div class="small text-muted">{{ $trainer->trainer_email }}</div></td><td>{{ $trainer->specialization }}</td><td>{{ number_format($trainer->hourly_rate, 2) }}</td><td>{{ number_format($trainer->commission_percent, 2) }}%</td><td><span class="status-pill">{{ $trainer->status }}</span></td></tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">No trainer profiles yet.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            {{ $trainerProfiles->links() }}
        </div>
    @break

    @case('attendance')
        <div class="fitness-metrics mb-3">
            <div class="fitness-metric"><div class="label">Currently In Gym</div><div class="value">{{ number_format($currentlyInGym) }}</div></div>
            <div class="fitness-metric"><div class="label">Capacity Limit</div><div class="value">{{ number_format($capacityLimit) }}</div></div>
            <div class="fitness-metric"><div class="label">Occupancy</div><div class="value">{{ $occupancyPercent }}%</div></div>
            <div class="fitness-metric"><div class="label">Export</div><div class="value"><a href="{{ route('fitness.attendance.export') }}">CSV</a></div></div>
        </div>
        <div class="card p-3">
            <h3 class="h6 mb-3">Attendance History</h3>
            <div class="table-responsive"><table class="table align-middle">
                <thead><tr><th>Member</th><th>Method</th><th>Entry</th><th>Exit</th><th>Duration</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($attendanceLogs as $log)
                        <tr><td>{{ $log->member_name }}<div class="small text-muted">{{ $log->member_number }}</div></td><td>{{ $log->method }}</td><td>{{ $log->entry_time }}</td><td>{{ $log->exit_time ?: '-' }}</td><td>{{ $log->visit_minutes ? $log->visit_minutes.' min' : '-' }}</td><td><span class="status-pill">{{ $log->status }}</span></td></tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">No attendance logs yet.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            {{ $attendanceLogs->links() }}
        </div>
    @break

    @case('check-in')
        <div class="fitness-two">
            <div class="card p-3">
                <h3 class="h6 mb-3">Check Member In</h3>
                <form method="post" action="{{ route('fitness.check-in.store') }}" class="vstack gap-2">
                    @csrf
                    <input class="form-control" name="member_identifier" placeholder="Membership ID, member ID, QR code, or database ID" required>
                    <select class="form-select" name="method"><option>Manual</option><option>QR Code</option><option>Barcode</option><option>RFID</option><option>Membership Card</option><option>Mobile Check-In</option></select>
                    <textarea class="form-control" name="notes" rows="2" placeholder="Notes"></textarea>
                    <button class="btn btn-warning">Check In</button>
                </form>
            </div>
            <div class="card p-3">
                <h3 class="h6 mb-3">Check Member Out</h3>
                <form method="post" action="{{ route('fitness.check-out.store') }}" class="vstack gap-2">
                    @csrf
                    <input class="form-control" name="member_identifier" placeholder="Membership ID, member ID, QR code, or database ID" required>
                    <button class="btn btn-outline-dark">Check Out</button>
                </form>
            </div>
        </div>
        <div class="card p-3 mt-3">
            <h3 class="h6 mb-3">Eligible For Check-In</h3>
            <div class="table-responsive"><table class="table align-middle">
                <thead><tr><th>Member</th><th>Membership ID</th><th>Member ID</th><th>QR / Scan Code</th><th>Plan</th><th>Credits</th></tr></thead>
                <tbody>
                    @forelse($eligibleMemberships as $membership)
                        <tr>
                            <td><strong>{{ $membership->member_name }}</strong><div class="small text-muted">{{ $membership->member_status }}</div></td>
                            <td><code>{{ $membership->membership_number }}</code></td>
                            <td><code>{{ $membership->member_number }}</code></td>
                            <td><code class="small text-break">{{ $membership->qr_code }}</code></td>
                            <td>{{ $membership->plan_name }}<div class="small text-muted">Ends {{ $membership->ends_at ?: '-' }}</div></td>
                            <td>{{ $membership->session_credits_remaining === null ? 'Unlimited' : $membership->session_credits_remaining }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">No members currently have an active, valid membership. Enroll or renew a member before check-in.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
        <div class="card p-3 mt-3">
            <h3 class="h6 mb-3">Recent Membership Status</h3>
            <div class="table-responsive"><table class="table align-middle">
                <thead><tr><th>Member</th><th>Membership ID</th><th>Plan</th><th>Period</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($recentMemberships as $membership)
                        <tr>
                            <td>{{ $membership->member_name }}<div class="small text-muted">{{ $membership->member_number }}</div></td>
                            <td><code>{{ $membership->membership_number }}</code></td>
                            <td>{{ $membership->plan_name }}</td>
                            <td>{{ $membership->starts_at }} - {{ $membership->ends_at ?: '-' }}</td>
                            <td><span class="status-pill">{{ $membership->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No membership enrollments yet.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
        <div class="card p-3 mt-3">
            <h3 class="h6 mb-3">Currently In Gym</h3>
            <div class="table-responsive"><table class="table align-middle">
                <thead><tr><th>Member</th><th>Method</th><th>Entry Time</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($openVisits as $visit)
                        <tr><td>{{ $visit->member_name }}<div class="small text-muted">{{ $visit->member_number }}</div></td><td>{{ $visit->method }}</td><td>{{ $visit->entry_time }}</td><td><span class="status-pill">{{ $visit->status }}</span></td></tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No open visits.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            {{ $openVisits->links() }}
        </div>
    @break

    @case('classes')
        <div class="fitness-two">
            <div class="card p-3">
                <h3 class="h6 mb-3">Create Class</h3>
                <form method="post" action="{{ route('fitness.classes.store') }}" class="fitness-form-grid">
                    @csrf
                    <input class="form-control span-2" name="name" placeholder="Class name" required>
                    <select class="form-select" name="fitness_class_type_id"><option value="">Type</option>@foreach($classTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select>
                    <select class="form-select" name="trainer_id"><option value="">Instructor</option>@foreach($trainers as $trainer)<option value="{{ $trainer->user_id }}">{{ $trainer->trainer_name }}</option>@endforeach</select>
                    <input class="form-control" name="capacity" type="number" min="1" value="20" placeholder="Capacity">
                    <input class="form-control" name="duration_minutes" type="number" min="15" value="60" placeholder="Duration">
                    <input class="form-control" name="room" placeholder="Room">
                    <input class="form-control" name="level" placeholder="Level">
                    <input class="form-control" name="drop_in_price" type="number" min="0" step="0.01" placeholder="Drop-in price">
                    <select class="form-select" name="status"><option>Active</option><option>Inactive</option></select>
                    <textarea class="form-control span-4" name="description" rows="2" placeholder="Description"></textarea>
                    <button class="btn btn-warning span-4">Save Class</button>
                </form>
            </div>
            <div class="card p-3">
                <h3 class="h6 mb-3">Schedule Session</h3>
                <form method="post" action="{{ route('fitness.class-sessions.store') }}" class="vstack gap-2">
                    @csrf
                    <select class="form-select" name="fitness_class_id" required><option value="">Class</option>@foreach($classOptions as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select>
                    <select class="form-select" name="trainer_id"><option value="">Default trainer</option>@foreach($trainers as $trainer)<option value="{{ $trainer->user_id }}">{{ $trainer->trainer_name }}</option>@endforeach</select>
                    <input class="form-control" name="starts_at" type="datetime-local" required>
                    <input class="form-control" name="ends_at" type="datetime-local" required>
                    <input class="form-control" name="capacity" type="number" min="1" placeholder="Override capacity">
                    <select class="form-select" name="status"><option>Scheduled</option><option>Cancelled</option><option>Completed</option></select>
                    <button class="btn btn-outline-dark">Schedule</button>
                </form>
            </div>
        </div>
        <div class="card p-3 mt-3">
            <h3 class="h6 mb-3">Book Member</h3>
            <form method="post" action="{{ route('fitness.class-bookings.store') }}" class="fitness-form-grid">
                @csrf
                <select class="form-select span-2" name="fitness_class_session_id" required><option value="">Session</option>@foreach($sessionOptions as $session)<option value="{{ $session->id }}">{{ $session->class_name }} - {{ $session->starts_at }}</option>@endforeach</select>
                <select class="form-select span-2" name="fitness_member_id" required><option value="">Member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->member_number }} - {{ $member->client?->name }}</option>@endforeach</select>
                <input class="form-control span-4" name="notes" placeholder="Booking notes">
                <button class="btn btn-warning span-4">Book or Waitlist</button>
            </form>
        </div>
        <div class="fitness-two mt-3">
            <div class="card p-3">
                <h3 class="h6 mb-3">Classes</h3>
                <div class="table-responsive"><table class="table"><thead><tr><th>Class</th><th>Instructor</th><th>Capacity</th><th>Status</th></tr></thead><tbody>@forelse($classes as $class)<tr><td>{{ $class->name }}<div class="small text-muted">{{ $class->class_type }} · {{ $class->room }}</div></td><td>{{ $class->trainer_name ?: '-' }}</td><td>{{ $class->capacity }}</td><td><span class="status-pill">{{ $class->status }}</span></td></tr>@empty<tr><td colspan="4" class="text-muted">No classes yet.</td></tr>@endforelse</tbody></table></div>
                {{ $classes->links() }}
            </div>
            <div class="card p-3">
                <h3 class="h6 mb-3">Bookings</h3>
                <div class="table-responsive"><table class="table"><thead><tr><th>Session</th><th>Member</th><th>Status</th><th>Mark</th></tr></thead><tbody>@forelse($bookings as $booking)<tr><td>{{ $booking->class_name }}<div class="small text-muted">{{ $booking->starts_at }}</div></td><td>{{ $booking->member_name }}<div class="small text-muted">{{ $booking->member_number }}</div></td><td><span class="status-pill">{{ $booking->status }}</span></td><td><form method="post" action="{{ route('fitness.class-bookings.attendance', $booking->id) }}" class="d-flex gap-1">@csrf @method('patch')<select class="form-select form-select-sm" name="status"><option>Present</option><option>Absent</option><option>Late</option><option>No Show</option><option>Cancelled</option></select><button class="btn btn-sm btn-outline-dark">Save</button></form></td></tr>@empty<tr><td colspan="4" class="text-muted">No bookings yet.</td></tr>@endforelse</tbody></table></div>
            </div>
        </div>
    @break

    @case('programs')
        <div class="fitness-two">
            <div class="card p-3">
                <h3 class="h6 mb-3">Program Template</h3>
                <form method="post" action="{{ route('fitness.programs.store') }}" class="fitness-form-grid">
                    @csrf
                    <input class="form-control span-2" name="name" placeholder="Program name" required>
                    <select class="form-select" name="program_type"><option>Weight Loss</option><option>Muscle Gain</option><option>Strength</option><option>Cardio</option><option>Rehabilitation</option><option>Athletic Performance</option></select>
                    <select class="form-select" name="difficulty"><option>Beginner</option><option>Intermediate</option><option>Advanced</option></select>
                    <select class="form-select" name="trainer_id"><option value="">Trainer</option>@foreach($trainers as $trainer)<option value="{{ $trainer->user_id }}">{{ $trainer->trainer_name }}</option>@endforeach</select>
                    <input class="form-control" name="duration_weeks" type="number" min="1" value="4">
                    <input class="form-control" name="price" type="number" step="0.01" min="0" placeholder="Price">
                    <select class="form-select" name="status"><option>Active</option><option>Inactive</option></select>
                    <label class="form-check span-4"><input class="form-check-input" name="is_public" type="checkbox" value="1"> Public program</label>
                    <textarea class="form-control span-4" name="structure" rows="3" placeholder="Weekly schedule and workout structure"></textarea>
                    <textarea class="form-control span-4" name="description" rows="2" placeholder="Description"></textarea>
                    <button class="btn btn-warning span-4">Save Program</button>
                </form>
            </div>
            <div class="card p-3">
                <h3 class="h6 mb-3">Assign Program</h3>
                <form method="post" action="{{ route('fitness.program-assignments.store') }}" class="vstack gap-2">
                    @csrf
                    <select class="form-select" name="fitness_program_id" required><option value="">Program</option>@foreach($programOptions as $program)<option value="{{ $program->id }}">{{ $program->name }}</option>@endforeach</select>
                    <select class="form-select" name="fitness_member_id" required><option value="">Member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->member_number }} - {{ $member->client?->name }}</option>@endforeach</select>
                    <select class="form-select" name="trainer_id"><option value="">Trainer</option>@foreach($trainers as $trainer)<option value="{{ $trainer->user_id }}">{{ $trainer->trainer_name }}</option>@endforeach</select>
                    <input class="form-control" name="starts_at" type="date" value="{{ now()->toDateString() }}" required>
                    <input class="form-control" name="ends_at" type="date">
                    <input class="form-control" name="adherence_percent" type="number" min="0" max="100" step="0.01" placeholder="Adherence %">
                    <select class="form-select" name="status"><option>Active</option><option>Paused</option><option>Completed</option><option>Cancelled</option></select>
                    <button class="btn btn-outline-dark">Assign</button>
                </form>
            </div>
        </div>
        <div class="card p-3 mt-3"><h3 class="h6 mb-3">Programs</h3><div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Type</th><th>Trainer</th><th>Duration</th><th>Status</th></tr></thead><tbody>@forelse($programs as $program)<tr><td>{{ $program->name }}</td><td>{{ $program->program_type }}</td><td>{{ $program->trainer_name ?: '-' }}</td><td>{{ $program->duration_weeks }} weeks</td><td><span class="status-pill">{{ $program->status }}</span></td></tr>@empty<tr><td colspan="5" class="text-muted">No programs yet.</td></tr>@endforelse</tbody></table></div>{{ $programs->links() }}</div>
        <div class="card p-3 mt-3"><h3 class="h6 mb-3">Assignments</h3><div class="table-responsive"><table class="table"><thead><tr><th>Program</th><th>Member</th><th>Trainer</th><th>Adherence</th><th>Status</th></tr></thead><tbody>@forelse($programAssignments as $assignment)<tr><td>{{ $assignment->program_name }}</td><td>{{ $assignment->member_name }}</td><td>{{ $assignment->trainer_name ?: '-' }}</td><td>{{ number_format($assignment->adherence_percent, 2) }}%</td><td><span class="status-pill">{{ $assignment->status }}</span></td></tr>@empty<tr><td colspan="5" class="text-muted">No assignments yet.</td></tr>@endforelse</tbody></table></div></div>
    @break

    @case('exercises')
        <div class="card p-3 mb-3">
            <h3 class="h6 mb-3">Add Exercise</h3>
            <form method="post" action="{{ route('fitness.exercises.store') }}" class="fitness-form-grid">
                @csrf
                <input class="form-control span-2" name="name" placeholder="Exercise name" required>
                <select class="form-select" name="category"><option>Chest</option><option>Back</option><option>Legs</option><option>Shoulders</option><option>Arms</option><option>Core</option><option>Cardio</option><option>Flexibility</option></select>
                <select class="form-select" name="difficulty"><option>Beginner</option><option>Intermediate</option><option>Advanced</option></select>
                <input class="form-control" name="target_muscle" placeholder="Target muscle">
                <input class="form-control" name="equipment_required" placeholder="Equipment">
                <input class="form-control" name="video_url" type="url" placeholder="Video URL">
                <input class="form-control" name="image_url" type="url" placeholder="Image URL">
                <textarea class="form-control span-4" name="instructions" rows="2" placeholder="Instructions"></textarea>
                <button class="btn btn-warning span-4">Add Exercise</button>
            </form>
        </div>
        <div class="card p-3"><h3 class="h6 mb-3">Exercise Library</h3><div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Category</th><th>Target</th><th>Difficulty</th><th>Equipment</th></tr></thead><tbody>@forelse($exercises as $exercise)<tr><td>{{ $exercise->name }}</td><td>{{ $exercise->category }}</td><td>{{ $exercise->target_muscle }}</td><td>{{ $exercise->difficulty }}</td><td>{{ $exercise->equipment_required }}</td></tr>@empty<tr><td colspan="5" class="text-muted">No exercises yet.</td></tr>@endforelse</tbody></table></div>{{ $exercises->links() }}</div>
    @break

    @case('health-profiles')
        <div class="card p-3 mb-3">
            <h3 class="h6 mb-3">Health Profile</h3>
            <form method="post" action="{{ route('fitness.health-profiles.store') }}" class="fitness-form-grid">
                @csrf
                <select class="form-select span-2" name="fitness_member_id" required><option value="">Member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->member_number }} - {{ $member->client?->name }}</option>@endforeach</select>
                <input class="form-control" name="height_cm" type="number" step="0.01" placeholder="Height cm">
                <input class="form-control" name="weight_kg" type="number" step="0.01" placeholder="Weight kg">
                <input class="form-control" name="bmi" type="number" step="0.01" placeholder="BMI">
                <input class="form-control" name="body_fat_percentage" type="number" step="0.01" placeholder="Body fat %">
                <input class="form-control" name="muscle_mass" type="number" step="0.01" placeholder="Muscle mass">
                <input class="form-control" name="blood_pressure" placeholder="Blood pressure">
                <input class="form-control" name="resting_heart_rate" type="number" placeholder="Resting HR">
                <div class="span-4 d-flex flex-wrap gap-3">
                    @foreach(['Weight Loss','Weight Gain','Muscle Gain','Body Recomposition','Strength','Endurance','General Fitness','Rehabilitation'] as $goal)
                        <label class="form-check"><input class="form-check-input" name="goals[]" type="checkbox" value="{{ $goal }}"> {{ $goal }}</label>
                    @endforeach
                </div>
                <textarea class="form-control span-2" name="allergies" rows="2" placeholder="Allergies"></textarea>
                <textarea class="form-control span-2" name="medical_conditions" rows="2" placeholder="Medical conditions"></textarea>
                <textarea class="form-control span-2" name="injuries" rows="2" placeholder="Injuries"></textarea>
                <textarea class="form-control span-2" name="notes" rows="2" placeholder="Notes"></textarea>
                <button class="btn btn-warning span-4">Save Profile</button>
            </form>
        </div>
        <div class="card p-3"><h3 class="h6 mb-3">Profiles</h3><div class="table-responsive"><table class="table"><thead><tr><th>Member</th><th>Height</th><th>Weight</th><th>BMI</th><th>Body Fat</th><th>Resting HR</th></tr></thead><tbody>@forelse($healthProfiles as $profile)<tr><td>{{ $profile->member_name }}<div class="small text-muted">{{ $profile->member_number }}</div></td><td>{{ $profile->height_cm }}</td><td>{{ $profile->weight_kg }}</td><td>{{ $profile->bmi }}</td><td>{{ $profile->body_fat_percentage }}</td><td>{{ $profile->resting_heart_rate }}</td></tr>@empty<tr><td colspan="6" class="text-muted">No health profiles yet.</td></tr>@endforelse</tbody></table></div>{{ $healthProfiles->links() }}</div>
    @break

    @case('assessments')
        <div class="card p-3 mb-3">
            <h3 class="h6 mb-3">Record Assessment</h3>
            <form method="post" action="{{ route('fitness.assessments.store') }}" class="fitness-form-grid">
                @csrf
                <select class="form-select span-2" name="fitness_member_id" required><option value="">Member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->member_number }} - {{ $member->client?->name }}</option>@endforeach</select>
                <select class="form-select" name="trainer_id"><option value="">Trainer</option>@foreach($trainers as $trainer)<option value="{{ $trainer->user_id }}">{{ $trainer->trainer_name }}</option>@endforeach</select>
                <input class="form-control" name="assessment_date" type="date" value="{{ now()->toDateString() }}" required>
                <input class="form-control" name="weight_kg" type="number" step="0.01" placeholder="Weight">
                <input class="form-control" name="bmi" type="number" step="0.01" placeholder="BMI">
                <input class="form-control" name="body_fat_percentage" type="number" step="0.01" placeholder="Body fat %">
                <input class="form-control" name="muscle_mass" type="number" step="0.01" placeholder="Muscle mass">
                <input class="form-control" name="fitness_score" type="number" min="0" max="100" placeholder="Fitness score">
                <input class="form-control" name="strength_score" type="number" min="0" max="100" placeholder="Strength score">
                <input class="form-control" name="cardio_score" type="number" min="0" max="100" placeholder="Cardio score">
                <input class="form-control" name="flexibility_score" type="number" min="0" max="100" placeholder="Flexibility score">
                <textarea class="form-control span-4" name="notes" rows="2" placeholder="Notes"></textarea>
                <button class="btn btn-warning span-4">Record Assessment</button>
            </form>
        </div>
        <div class="card p-3"><h3 class="h6 mb-3">Assessment History</h3><div class="table-responsive"><table class="table"><thead><tr><th>Date</th><th>Member</th><th>Trainer</th><th>Weight</th><th>BMI</th><th>Fitness</th></tr></thead><tbody>@forelse($assessments as $assessment)<tr><td>{{ $assessment->assessment_date }}</td><td>{{ $assessment->member_name }}</td><td>{{ $assessment->trainer_name ?: '-' }}</td><td>{{ $assessment->weight_kg }}</td><td>{{ $assessment->bmi }}</td><td>{{ $assessment->fitness_score }}</td></tr>@empty<tr><td colspan="6" class="text-muted">No assessments yet.</td></tr>@endforelse</tbody></table></div>{{ $assessments->links() }}</div>
    @break

    @case('personal-training')
        <div class="fitness-two">
            <div class="card p-3">
                <h3 class="h6 mb-3">PT Package</h3>
                <form method="post" action="{{ route('fitness.pt-packages.store') }}" class="vstack gap-2">
                    @csrf
                    <input class="form-control" name="name" placeholder="Package name" required>
                    <input class="form-control" name="sessions_included" type="number" min="1" placeholder="Sessions included" required>
                    <input class="form-control" name="price" type="number" step="0.01" min="0" placeholder="Price" required>
                    <input class="form-control" name="validity_days" type="number" min="1" value="30" required>
                    <select class="form-select" name="status"><option>Active</option><option>Inactive</option></select>
                    <textarea class="form-control" name="description" rows="2" placeholder="Description"></textarea>
                    <button class="btn btn-warning">Save Package</button>
                </form>
            </div>
            <div class="card p-3">
                <h3 class="h6 mb-3">PT Session</h3>
                <form method="post" action="{{ route('fitness.pt-sessions.store') }}" class="vstack gap-2">
                    @csrf
                    <select class="form-select" name="fitness_member_id" required><option value="">Member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->member_number }} - {{ $member->client?->name }}</option>@endforeach</select>
                    <select class="form-select" name="trainer_id" required><option value="">Trainer</option>@foreach($trainers as $trainer)<option value="{{ $trainer->user_id }}">{{ $trainer->trainer_name }}</option>@endforeach</select>
                    <select class="form-select" name="fitness_pt_package_id"><option value="">Package</option>@foreach($ptPackages as $package)<option value="{{ $package->id }}">{{ $package->name }}</option>@endforeach</select>
                    <input class="form-control" name="scheduled_at" type="datetime-local" required>
                    <input class="form-control" name="duration_minutes" type="number" min="15" value="60" required>
                    <select class="form-select" name="status"><option>Scheduled</option><option>Completed</option><option>Cancelled</option><option>No Show</option></select>
                    <button class="btn btn-outline-dark">Save Session</button>
                </form>
            </div>
        </div>
        <div class="card p-3 mt-3"><h3 class="h6 mb-3">PT Sessions</h3><div class="table-responsive"><table class="table"><thead><tr><th>Member</th><th>Trainer</th><th>Package</th><th>Time</th><th>Status</th></tr></thead><tbody>@forelse($ptSessions as $session)<tr><td>{{ $session->member_name }}</td><td>{{ $session->trainer_name }}</td><td>{{ $session->package_name ?: '-' }}</td><td>{{ $session->scheduled_at }}</td><td><span class="status-pill">{{ $session->status }}</span></td></tr>@empty<tr><td colspan="5" class="text-muted">No PT sessions yet.</td></tr>@endforelse</tbody></table></div></div>
    @break

    @case('nutrition')
        <div class="fitness-two">
            <div class="card p-3">
                <h3 class="h6 mb-3">Nutrition Plan</h3>
                <form method="post" action="{{ route('fitness.nutrition-plans.store') }}" class="fitness-form-grid">
                    @csrf
                    <input class="form-control span-2" name="name" placeholder="Plan name" required>
                    <select class="form-select span-2" name="trainer_id"><option value="">Trainer</option>@foreach($trainers as $trainer)<option value="{{ $trainer->user_id }}">{{ $trainer->trainer_name }}</option>@endforeach</select>
                    <input class="form-control" name="calories" type="number" placeholder="Calories">
                    <input class="form-control" name="protein" type="number" placeholder="Protein">
                    <input class="form-control" name="carbohydrates" type="number" placeholder="Carbs">
                    <input class="form-control" name="fat" type="number" placeholder="Fat">
                    <input class="form-control" name="fiber" type="number" placeholder="Fiber">
                    <input class="form-control" name="water_intake_goal" type="number" placeholder="Water ml">
                    <select class="form-select" name="status"><option>Active</option><option>Inactive</option></select>
                    <textarea class="form-control span-4" name="meals" rows="2" placeholder="Breakfast, lunch, dinner, snacks"></textarea>
                    <textarea class="form-control span-4" name="description" rows="2" placeholder="Description"></textarea>
                    <button class="btn btn-warning span-4">Save Nutrition Plan</button>
                </form>
            </div>
            <div class="card p-3">
                <h3 class="h6 mb-3">Assign Nutrition</h3>
                <form method="post" action="{{ route('fitness.nutrition-assignments.store') }}" class="vstack gap-2">
                    @csrf
                    <select class="form-select" name="fitness_nutrition_plan_id" required><option value="">Plan</option>@foreach($nutritionOptions as $plan)<option value="{{ $plan->id }}">{{ $plan->name }}</option>@endforeach</select>
                    <select class="form-select" name="fitness_member_id" required><option value="">Member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->member_number }} - {{ $member->client?->name }}</option>@endforeach</select>
                    <input class="form-control" name="starts_at" type="date" value="{{ now()->toDateString() }}" required>
                    <input class="form-control" name="ends_at" type="date">
                    <input class="form-control" name="compliance_percent" type="number" min="0" max="100" step="0.01" placeholder="Compliance %">
                    <select class="form-select" name="status"><option>Active</option><option>Paused</option><option>Completed</option><option>Cancelled</option></select>
                    <button class="btn btn-outline-dark">Assign</button>
                </form>
            </div>
        </div>
        <div class="card p-3 mt-3"><h3 class="h6 mb-3">Nutrition Assignments</h3><div class="table-responsive"><table class="table"><thead><tr><th>Plan</th><th>Member</th><th>Compliance</th><th>Status</th><th></th></tr></thead><tbody>@forelse($nutritionAssignments as $assignment)<tr><td>{{ $assignment->plan_name }}</td><td>{{ $assignment->member_name }}</td><td>{{ number_format($assignment->compliance_percent, 2) }}%</td><td><span class="status-pill">{{ $assignment->status }}</span></td><td class="text-end"><a class="btn btn-sm btn-outline-dark" href="{{ route('fitness.nutrition-assignments.download', $assignment->id) }}"><i class="bi bi-download me-1"></i>Download</a></td></tr>@empty<tr><td colspan="5" class="text-muted">No nutrition assignments yet.</td></tr>@endforelse</tbody></table></div></div>
    @break

    @case('challenges')
        <div class="fitness-two">
            <div class="card p-3">
                <h3 class="h6 mb-3">Challenge</h3>
                <form method="post" action="{{ route('fitness.challenges.store') }}" class="vstack gap-2">
                    @csrf
                    <input class="form-control" name="name" placeholder="Challenge name" required>
                    <select class="form-select" name="challenge_type"><option>Weight Loss Challenge</option><option>Step Challenge</option><option>Strength Challenge</option><option>Cardio Challenge</option><option>Transformation Challenge</option></select>
                    <input class="form-control" name="starts_at" type="date" required>
                    <input class="form-control" name="ends_at" type="date" required>
                    <input class="form-control" name="reward" placeholder="Reward">
                    <select class="form-select" name="status"><option>Active</option><option>Completed</option><option>Cancelled</option></select>
                    <textarea class="form-control" name="description" rows="2" placeholder="Description"></textarea>
                    <button class="btn btn-warning">Save Challenge</button>
                </form>
            </div>
            <div class="card p-3">
                <h3 class="h6 mb-3">Participant</h3>
                <form method="post" action="{{ route('fitness.challenge-participants.store') }}" class="vstack gap-2">
                    @csrf
                    <select class="form-select" name="fitness_challenge_id" required><option value="">Challenge</option>@foreach($challengeOptions as $challenge)<option value="{{ $challenge->id }}">{{ $challenge->name }}</option>@endforeach</select>
                    <select class="form-select" name="fitness_member_id" required><option value="">Member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->member_number }} - {{ $member->client?->name }}</option>@endforeach</select>
                    <input class="form-control" name="progress_value" type="number" step="0.01" min="0" placeholder="Progress">
                    <input class="form-control" name="rank" type="number" min="1" placeholder="Rank">
                    <select class="form-select" name="status"><option>Active</option><option>Completed</option><option>Withdrawn</option></select>
                    <button class="btn btn-outline-dark">Save Participant</button>
                </form>
            </div>
        </div>
        <div class="card p-3 mt-3"><h3 class="h6 mb-3">Leaderboard</h3><div class="table-responsive"><table class="table"><thead><tr><th>Challenge</th><th>Member</th><th>Progress</th><th>Rank</th><th>Status</th></tr></thead><tbody>@forelse($challengeParticipants as $participant)<tr><td>{{ $participant->challenge_name }}</td><td>{{ $participant->member_name }}</td><td>{{ $participant->progress_value }}</td><td>{{ $participant->rank ?: '-' }}</td><td><span class="status-pill">{{ $participant->status }}</span></td></tr>@empty<tr><td colspan="5" class="text-muted">No challenge participants yet.</td></tr>@endforelse</tbody></table></div></div>
    @break

    @case('equipment')
        <div class="fitness-two">
            <div class="card p-3">
                <h3 class="h6 mb-3">Equipment</h3>
                <form method="post" action="{{ route('fitness.equipment.store') }}" class="fitness-form-grid">
                    @csrf
                    <input class="form-control" name="equipment_code" placeholder="Equipment ID">
                    <input class="form-control span-2" name="name" placeholder="Name" required>
                    <input class="form-control" name="category" placeholder="Category">
                    <input class="form-control" name="brand" placeholder="Brand">
                    <input class="form-control" name="model" placeholder="Model">
                    <input class="form-control" name="serial_number" placeholder="Serial number">
                    <input class="form-control" name="location" placeholder="Location">
                    <input class="form-control" name="cost" type="number" step="0.01" min="0" placeholder="Equipment cost">
                    <input class="form-control" name="purchase_date" type="date">
                    <input class="form-control" name="warranty_expires_at" type="date">
                    <select class="form-select" name="status"><option>Active</option><option>Maintenance</option><option>Retired</option></select>
                    <textarea class="form-control span-4" name="notes" rows="2" placeholder="Notes"></textarea>
                    <button class="btn btn-warning span-4">Save Equipment</button>
                </form>
            </div>
            <div class="card p-3">
                <h3 class="h6 mb-3">Maintenance</h3>
                <form method="post" action="{{ route('fitness.equipment-maintenance.store') }}" class="vstack gap-2">
                    @csrf
                    <select class="form-select" name="fitness_equipment_id" required><option value="">Equipment</option>@foreach($equipmentOptions as $item)<option value="{{ $item->id }}">{{ $item->equipment_code }} - {{ $item->name }}</option>@endforeach</select>
                    <input class="form-control" name="service_date" type="date" value="{{ now()->toDateString() }}" required>
                    <input class="form-control" name="next_service_date" type="date">
                    <input class="form-control" name="technician" placeholder="Technician">
                    <input class="form-control" name="cost" type="number" step="0.01" min="0" placeholder="Cost">
                    <textarea class="form-control" name="notes" rows="2" placeholder="Notes"></textarea>
                    <button class="btn btn-outline-dark">Record Maintenance</button>
                </form>
            </div>
        </div>
        <div class="card p-3 mt-3"><h3 class="h6 mb-3">Equipment Register</h3><div class="table-responsive"><table class="table"><thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Cost</th><th>Warranty</th><th>Location</th><th>Status</th></tr></thead><tbody>@forelse($equipment as $item)<tr><td>{{ $item->equipment_code }}</td><td>{{ $item->name }}<div class="small text-muted">{{ $item->brand }} {{ $item->model }}</div></td><td>{{ $item->category }}</td><td>{{ number_format($item->cost ?? 0, 2) }}</td><td>{{ $item->warranty_expires_at ?: '-' }}</td><td>{{ $item->location }}</td><td><span class="status-pill">{{ $item->status }}</span></td></tr>@empty<tr><td colspan="7" class="text-muted">No equipment yet.</td></tr>@endforelse</tbody></table></div>{{ $equipment->links() }}</div>
        <div class="card p-3 mt-3"><h3 class="h6 mb-3">Maintenance Logs</h3><div class="table-responsive"><table class="table"><thead><tr><th>Equipment</th><th>Service</th><th>Next</th><th>Technician</th><th>Cost</th></tr></thead><tbody>@forelse($maintenanceLogs as $log)<tr><td>{{ $log->equipment_code }} - {{ $log->equipment_name }}</td><td>{{ $log->service_date }}</td><td>{{ $log->next_service_date ?: '-' }}</td><td>{{ $log->technician ?: '-' }}</td><td>{{ number_format($log->cost, 2) }}</td></tr>@empty<tr><td colspan="5" class="text-muted">No maintenance logs yet.</td></tr>@endforelse</tbody></table></div></div>
    @break

    @case('reports')
        <div class="fitness-metrics mb-3">
            @foreach($reportMetrics as $label => $value)
                <div class="fitness-metric"><div class="label">{{ $label }}</div><div class="value">{{ is_numeric($value) ? number_format($value, str_contains($label, 'Revenue') ? 2 : 0) : $value }}</div></div>
            @endforeach
        </div>
        <div class="fitness-two">
            <div class="card p-3">
                <h3 class="h6 mb-3">Top Trainers</h3>
                <div class="table-responsive"><table class="table"><thead><tr><th>Trainer</th><th>Completed Sessions</th></tr></thead><tbody>@forelse($topTrainers as $trainer)<tr><td>{{ $trainer->name }}</td><td>{{ $trainer->sessions_completed }}</td></tr>@empty<tr><td colspan="2" class="text-muted">No completed PT sessions yet.</td></tr>@endforelse</tbody></table></div>
            </div>
            <div class="card p-3">
                <h3 class="h6 mb-3">Body Metric Trends</h3>
                <div class="table-responsive"><table class="table"><thead><tr><th>Date</th><th>Member</th><th>Weight</th><th>BMI</th><th>Body Fat</th></tr></thead><tbody>@forelse($bodyTrends as $trend)<tr><td>{{ $trend->assessment_date }}</td><td>{{ $trend->member_name }}</td><td>{{ $trend->weight_kg }}</td><td>{{ $trend->bmi }}</td><td>{{ $trend->body_fat_percentage }}</td></tr>@empty<tr><td colspan="5" class="text-muted">No assessments yet.</td></tr>@endforelse</tbody></table></div>
            </div>
        </div>
    @break
@endswitch
@endsection
