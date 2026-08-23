<?php

namespace Modules\Fitness\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\Payment;
use App\Models\User;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Fitness\Models\Member;
use Modules\Fitness\Services\FitnessFeatureGate;

class FitnessOperationsController extends Controller
{
    public function trainers(FitnessFeatureGate $gate)
    {
        $gate->authorize('trainers');

        return $this->view('trainers', [
            'trainerProfiles' => $this->trainerQuery()->paginate(20),
        ]);
    }

    public function storeTrainer(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('trainers');

        $data = $request->validate([
            'user_id' => ['required', $this->activeBusinessUserExistsRule()],
            'trainer_code' => ['nullable', 'string', 'max:50'],
            'specialization' => ['nullable', 'string', 'max:1000'],
            'certifications' => ['nullable', 'string', 'max:2000'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'availability' => ['nullable', 'string', 'max:2000'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_clients' => ['nullable', 'integer', 'min:0'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'status' => ['required', 'in:Active,Inactive,Suspended'],
            'bio' => ['nullable', 'string', 'max:4000'],
        ]);

        $data['trainer_code'] = $data['trainer_code'] ?: $this->nextCode('fitness_trainer_profiles', 'trainer_code', 'TRN');
        $data['certifications'] = $this->linesToJson($data['certifications'] ?? null);
        $data['availability'] = $this->linesToJson($data['availability'] ?? null);

        DB::table('fitness_trainer_profiles')->updateOrInsert(
            ['business_id' => $this->businessId(), 'user_id' => $data['user_id']],
            $this->payload($data)
        );

        return back()->with('status', 'Trainer profile saved.');
    }

    public function attendance(FitnessFeatureGate $gate)
    {
        $gate->authorize('attendance');

        $capacity = $this->fitnessSettings()->capacity_limit ?? 100;
        $currentlyInGym = $this->scoped('fitness_attendance_logs')->whereNull('exit_time')->count();

        return $this->view('attendance', [
            'attendanceLogs' => $this->attendanceQuery()->paginate(30),
            'currentlyInGym' => $currentlyInGym,
            'capacityLimit' => $capacity,
            'occupancyPercent' => min(100, round(($currentlyInGym / max(1, $capacity)) * 100)),
        ]);
    }

    public function checkInIndex(FitnessFeatureGate $gate)
    {
        $gate->authorize('attendance');

        return $this->view('check-in', [
            'openVisits' => $this->attendanceQuery()->whereNull('fitness_attendance_logs.exit_time')->paginate(20),
            'eligibleMemberships' => $this->eligibleMembershipsQuery()->limit(30)->get(),
            'recentMemberships' => $this->recentMembershipsQuery()->limit(20)->get(),
        ]);
    }

    public function checkIn(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('attendance');

        $data = $request->validate([
            'member_identifier' => ['required', 'string', 'max:255'],
            'method' => ['required', 'in:QR Code,Barcode,RFID,Membership Card,Mobile Check-In,Manual'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $member = $this->findMember($data['member_identifier']);
        if (! $member) {
            throw ValidationException::withMessages(['member_identifier' => 'No member was found for that code or number.']);
        }

        DB::transaction(function () use ($member, $data) {
            $membership = DB::table('fitness_member_memberships')
                ->where('business_id', $this->businessId())
                ->where('fitness_member_id', $member->id)
                ->where('status', 'Active')
                ->where(function ($query) {
                    $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', now()->toDateString());
                })
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (! $membership) {
                throw ValidationException::withMessages(['member_identifier' => $this->membershipAccessMessage((int) $member->id)]);
            }

            if ($membership->session_credits_remaining !== null && (int) $membership->session_credits_remaining <= 0) {
                throw ValidationException::withMessages(['member_identifier' => 'This membership has no remaining session credits.']);
            }

            $openVisit = $this->scoped('fitness_attendance_logs')
                ->where('fitness_member_id', $member->id)
                ->whereNull('exit_time')
                ->lockForUpdate()
                ->exists();

            if ($openVisit) {
                throw ValidationException::withMessages(['member_identifier' => 'This member is already checked in.']);
            }

            DB::table('fitness_attendance_logs')->insert($this->payload([
                'fitness_member_id' => $member->id,
                'person_type' => 'Member',
                'method' => $data['method'],
                'entry_time' => now(),
                'status' => 'In Gym',
                'notes' => $data['notes'] ?? null,
            ]));

            if ($membership->session_credits_remaining !== null) {
                DB::table('fitness_member_memberships')
                    ->where('id', $membership->id)
                    ->decrement('session_credits_remaining');
            }
        });

        return back()->with('status', $member->member_number.' checked in.');
    }

    public function checkOut(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('attendance');

        $data = $request->validate([
            'member_identifier' => ['required', 'string', 'max:255'],
        ]);

        $member = $this->findMember($data['member_identifier']);
        if (! $member) {
            throw ValidationException::withMessages(['member_identifier' => 'No member was found for that code or number.']);
        }

        $visit = $this->scoped('fitness_attendance_logs')
            ->where('fitness_member_id', $member->id)
            ->whereNull('exit_time')
            ->latest('entry_time')
            ->first();

        if (! $visit) {
            throw ValidationException::withMessages(['member_identifier' => 'This member does not have an open check-in.']);
        }

        $exit = now();
        $visitMinutes = (int) floor(Carbon::parse($visit->entry_time)->diffInMinutes($exit));

        DB::table('fitness_attendance_logs')->where('id', $visit->id)->update([
            'exit_time' => $exit,
            'visit_minutes' => $visitMinutes,
            'status' => 'Checked Out',
            'updated_at' => now(),
        ]);

        return back()->with('status', $member->member_number.' checked out.');
    }

    public function attendanceCsv(FitnessFeatureGate $gate)
    {
        $gate->authorize('attendance');

        $rows = $this->attendanceQuery()->limit(2000)->get();

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Member', 'Member Number', 'Method', 'Entry Time', 'Exit Time', 'Minutes', 'Status']);
            foreach ($rows as $row) {
                fputcsv($handle, [$row->member_name, $row->member_number, $row->method, $row->entry_time, $row->exit_time, $row->visit_minutes, $row->status]);
            }
            fclose($handle);
        }, 'fitness-attendance.csv');
    }

    public function classes(FitnessFeatureGate $gate)
    {
        $gate->authorize('classes');

        return $this->view('classes', [
            'classes' => $this->classesQuery()->paginate(20),
            'sessions' => $this->classSessionsQuery()->limit(25)->get(),
            'bookings' => $this->classBookingsQuery()->limit(40)->get(),
        ]);
    }

    public function storeClass(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('classes');

        $data = $request->validate([
            'fitness_class_type_id' => ['nullable', 'exists:fitness_class_types,id'],
            'trainer_id' => ['nullable', $this->activeBusinessUserExistsRule()],
            'name' => ['required', 'string', 'max:255'],
            'room' => ['nullable', 'string', 'max:100'],
            'level' => ['nullable', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'drop_in_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:Active,Inactive'],
            'description' => ['nullable', 'string', 'max:4000'],
        ]);

        DB::table('fitness_classes')->insert($this->payload($data));

        return back()->with('status', 'Class created.');
    }

    public function storeClassSession(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('classes');

        $data = $request->validate([
            'fitness_class_id' => ['required', Rule::exists('fitness_classes', 'id')->where('business_id', $this->businessId())],
            'trainer_id' => ['nullable', $this->activeBusinessUserExistsRule()],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'status' => ['required', 'in:Scheduled,Cancelled,Completed'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $class = $this->scoped('fitness_classes')->where('id', $data['fitness_class_id'])->first();
        $data['trainer_id'] = $data['trainer_id'] ?: $class->trainer_id;
        $data['capacity'] = $data['capacity'] ?: $class->capacity;

        $this->assertNoClassConflict($data, $class);
        DB::table('fitness_class_sessions')->insert($this->payload($data));

        return back()->with('status', 'Class session scheduled.');
    }

    public function bookClass(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('classes');

        $data = $request->validate([
            'fitness_class_session_id' => ['required', Rule::exists('fitness_class_sessions', 'id')->where('business_id', $this->businessId())],
            'fitness_member_id' => ['required', Rule::exists('fitness_members', 'id')->where('business_id', $this->businessId())],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $status = DB::transaction(function () use ($data) {
            $this->assertActiveMember((int) $data['fitness_member_id']);

            $session = $this->scoped('fitness_class_sessions')
                ->where('id', $data['fitness_class_session_id'])
                ->lockForUpdate()
                ->first();

            if (! $session || $session->status !== 'Scheduled') {
                throw ValidationException::withMessages(['fitness_class_session_id' => 'Only scheduled sessions can be booked.']);
            }

            $booked = $this->scoped('fitness_class_bookings')
                ->where('fitness_class_session_id', $session->id)
                ->whereIn('status', ['Booked', 'Present', 'Late'])
                ->count();

            $status = $booked >= (int) $session->capacity ? 'Waitlisted' : 'Booked';

            DB::table('fitness_class_bookings')->updateOrInsert(
                [
                    'business_id' => $this->businessId(),
                    'fitness_class_session_id' => $session->id,
                    'fitness_member_id' => $data['fitness_member_id'],
                ],
                $this->payload([
                    'status' => $status,
                    'booked_at' => now(),
                    'notes' => $data['notes'] ?? null,
                ])
            );

            return $status;
        });

        return back()->with('status', 'Class booking saved as '.$status.'.');
    }

    public function markClassAttendance(Request $request, int $booking, FitnessFeatureGate $gate)
    {
        $gate->authorize('classes');

        $data = $request->validate([
            'status' => ['required', 'in:Present,Absent,Late,No Show,Cancelled'],
        ]);

        $bookingRow = $this->scoped('fitness_class_bookings')->where('id', $booking)->first();
        abort_unless($bookingRow, 404);
        DB::table('fitness_class_bookings')->where('id', $bookingRow->id)->update([
            'status' => $data['status'],
            'attended_at' => in_array($data['status'], ['Present', 'Late'], true) ? now() : null,
            'updated_at' => now(),
        ]);

        if ($data['status'] === 'Cancelled' && ($this->fitnessSettings()->auto_promote_waitlist ?? true)) {
            $this->promoteWaitlist((int) $bookingRow->fitness_class_session_id);
        }

        return back()->with('status', 'Class attendance updated.');
    }

    public function programs(FitnessFeatureGate $gate)
    {
        $gate->authorize('programs');

        return $this->view('programs', [
            'programs' => $this->programsQuery()->paginate(20),
            'programAssignments' => $this->programAssignmentsQuery()->limit(40)->get(),
        ]);
    }

    public function storeProgram(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('programs');

        $data = $request->validate([
            'trainer_id' => ['nullable', $this->activeBusinessUserExistsRule()],
            'name' => ['required', 'string', 'max:255'],
            'program_type' => ['required', 'in:Weight Loss,Muscle Gain,Strength,Cardio,Rehabilitation,Athletic Performance'],
            'difficulty' => ['required', 'in:Beginner,Intermediate,Advanced'],
            'duration_weeks' => ['required', 'integer', 'min:1', 'max:104'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_public' => ['nullable', 'boolean'],
            'structure' => ['nullable', 'string', 'max:8000'],
            'status' => ['required', 'in:Active,Inactive'],
            'description' => ['nullable', 'string', 'max:4000'],
        ]);

        $data['is_public'] = (bool) ($data['is_public'] ?? false);
        $data['structure'] = $data['structure'] ? json_encode(['notes' => $data['structure']]) : null;
        DB::table('fitness_programs')->insert($this->payload($data));

        return back()->with('status', 'Fitness program saved.');
    }

    public function assignProgram(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('programs');

        $data = $request->validate([
            'fitness_program_id' => ['required', Rule::exists('fitness_programs', 'id')->where('business_id', $this->businessId())],
            'fitness_member_id' => ['required', Rule::exists('fitness_members', 'id')->where('business_id', $this->businessId())],
            'trainer_id' => ['nullable', $this->activeBusinessUserExistsRule()],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'adherence_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'in:Active,Paused,Completed,Cancelled'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->assertActiveMember((int) $data['fitness_member_id']);
        DB::table('fitness_program_assignments')->insert($this->payload($data));

        return back()->with('status', 'Program assigned.');
    }

    public function exercises(FitnessFeatureGate $gate)
    {
        $gate->authorize('exercises');

        return $this->view('exercises', [
            'exercises' => $this->exerciseQuery()->paginate(30),
        ]);
    }

    public function storeExercise(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('exercises');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:Chest,Back,Legs,Shoulders,Arms,Core,Cardio,Flexibility'],
            'target_muscle' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['required', 'in:Beginner,Intermediate,Advanced'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'equipment_required' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        DB::table('fitness_exercises')->insert($this->payload($data));

        return back()->with('status', 'Exercise added.');
    }

    public function healthProfiles(FitnessFeatureGate $gate)
    {
        $gate->authorize('health');

        return $this->view('health-profiles', [
            'healthProfiles' => $this->healthQuery()->paginate(20),
        ]);
    }

    public function storeHealthProfile(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('health');

        $data = $request->validate([
            'fitness_member_id' => ['required', Rule::exists('fitness_members', 'id')->where('business_id', $this->businessId())],
            'height_cm' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:600'],
            'bmi' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'body_fat_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'muscle_mass' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'blood_pressure' => ['nullable', 'string', 'max:50'],
            'resting_heart_rate' => ['nullable', 'integer', 'min:20', 'max:250'],
            'allergies' => ['nullable', 'string', 'max:2000'],
            'medical_conditions' => ['nullable', 'string', 'max:2000'],
            'injuries' => ['nullable', 'string', 'max:2000'],
            'goals' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $data['goals'] = isset($data['goals']) ? json_encode(array_values($data['goals'])) : null;
        DB::table('fitness_health_profiles')->updateOrInsert(
            ['business_id' => $this->businessId(), 'fitness_member_id' => $data['fitness_member_id']],
            $this->payload($data)
        );

        return back()->with('status', 'Health profile saved.');
    }

    public function assessments(FitnessFeatureGate $gate)
    {
        $gate->authorize('assessments');

        return $this->view('assessments', [
            'assessments' => $this->assessmentQuery()->paginate(25),
        ]);
    }

    public function storeAssessment(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('assessments');

        $data = $request->validate([
            'fitness_member_id' => ['required', Rule::exists('fitness_members', 'id')->where('business_id', $this->businessId())],
            'trainer_id' => ['nullable', $this->activeBusinessUserExistsRule()],
            'assessment_date' => ['required', 'date'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:600'],
            'bmi' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'body_fat_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'muscle_mass' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'fitness_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'strength_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'cardio_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'flexibility_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        DB::table('fitness_assessments')->insert($this->payload($data));

        return back()->with('status', 'Assessment recorded.');
    }

    public function personalTraining(FitnessFeatureGate $gate)
    {
        $gate->authorize('personal-training');

        return $this->view('personal-training', [
            'ptPackages' => $this->scoped('fitness_pt_packages')->latest()->paginate(20),
            'ptSessions' => $this->ptSessionsQuery()->limit(40)->get(),
        ]);
    }

    public function storePtPackage(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('personal-training');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sessions_included' => ['required', 'integer', 'min:1', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'validity_days' => ['required', 'integer', 'min:1', 'max:3660'],
            'status' => ['required', 'in:Active,Inactive'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::table('fitness_pt_packages')->insert($this->payload($data));

        return back()->with('status', 'PT package saved.');
    }

    public function storePtSession(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('personal-training');

        $data = $request->validate([
            'fitness_member_id' => ['required', Rule::exists('fitness_members', 'id')->where('business_id', $this->businessId())],
            'trainer_id' => ['required', $this->activeBusinessUserExistsRule()],
            'fitness_pt_package_id' => ['nullable', Rule::exists('fitness_pt_packages', 'id')->where('business_id', $this->businessId())],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'status' => ['required', 'in:Scheduled,Completed,Cancelled,No Show'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->assertActiveMember((int) $data['fitness_member_id']);
        $this->assertNoPtConflict($data);
        DB::table('fitness_pt_sessions')->insert($this->payload($data));

        return back()->with('status', 'PT session saved.');
    }

    public function nutrition(FitnessFeatureGate $gate)
    {
        $gate->authorize('nutrition');

        return $this->view('nutrition', [
            'nutritionPlans' => $this->scoped('fitness_nutrition_plans')->latest()->paginate(20),
            'nutritionAssignments' => $this->nutritionAssignmentsQuery()->limit(40)->get(),
        ]);
    }

    public function storeNutritionPlan(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('nutrition');

        $data = $request->validate([
            'trainer_id' => ['nullable', $this->activeBusinessUserExistsRule()],
            'name' => ['required', 'string', 'max:255'],
            'calories' => ['nullable', 'integer', 'min:0', 'max:20000'],
            'protein' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'carbohydrates' => ['nullable', 'integer', 'min:0', 'max:3000'],
            'fat' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'fiber' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'water_intake_goal' => ['nullable', 'integer', 'min:0', 'max:20000'],
            'meals' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:Active,Inactive'],
            'description' => ['nullable', 'string', 'max:4000'],
        ]);

        $data['meals'] = $data['meals'] ? json_encode(['notes' => $data['meals']]) : null;
        DB::table('fitness_nutrition_plans')->insert($this->payload($data));

        return back()->with('status', 'Nutrition plan saved.');
    }

    public function assignNutritionPlan(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('nutrition');

        $data = $request->validate([
            'fitness_nutrition_plan_id' => ['required', Rule::exists('fitness_nutrition_plans', 'id')->where('business_id', $this->businessId())],
            'fitness_member_id' => ['required', Rule::exists('fitness_members', 'id')->where('business_id', $this->businessId())],
            'trainer_id' => ['nullable', $this->activeBusinessUserExistsRule()],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'compliance_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'in:Active,Paused,Completed,Cancelled'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->assertActiveMember((int) $data['fitness_member_id']);
        DB::table('fitness_nutrition_assignments')->insert($this->payload($data));

        return back()->with('status', 'Nutrition plan assigned.');
    }

    public function downloadNutritionAssignment(int $assignment, FitnessFeatureGate $gate)
    {
        $gate->authorize('nutrition');

        $record = $this->nutritionAssignmentDownloadQuery()
            ->where('fitness_nutrition_assignments.id', $assignment)
            ->firstOrFail();

        $meals = json_decode($record->meals ?? '', true);
        $mealNotes = is_array($meals) ? ($meals['notes'] ?? null) : null;
        $filename = str($record->member_name.'-'.$record->plan_name.'-nutrition-plan')->slug().'.pdf';

        return Pdf::loadView('fitness.pdf.nutrition-plan', [
            'assignment' => $record,
            'mealNotes' => $mealNotes,
            'settings' => CompanySetting::where('business_id', $this->businessId())->first() ?: CompanySetting::first(),
            'business' => ActiveBusiness::current(),
        ])->download($filename);
    }

    public function challenges(FitnessFeatureGate $gate)
    {
        $gate->authorize('challenges');

        return $this->view('challenges', [
            'challenges' => $this->scoped('fitness_challenges')->latest()->paginate(20),
            'challengeParticipants' => $this->challengeParticipantsQuery()->limit(40)->get(),
        ]);
    }

    public function storeChallenge(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('challenges');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'challenge_type' => ['required', 'in:Weight Loss Challenge,Step Challenge,Strength Challenge,Cardio Challenge,Transformation Challenge'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'reward' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Completed,Cancelled'],
            'description' => ['nullable', 'string', 'max:4000'],
        ]);

        DB::table('fitness_challenges')->insert($this->payload($data));

        return back()->with('status', 'Challenge saved.');
    }

    public function joinChallenge(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('challenges');

        $data = $request->validate([
            'fitness_challenge_id' => ['required', Rule::exists('fitness_challenges', 'id')->where('business_id', $this->businessId())],
            'fitness_member_id' => ['required', Rule::exists('fitness_members', 'id')->where('business_id', $this->businessId())],
            'progress_value' => ['nullable', 'numeric', 'min:0'],
            'rank' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:Active,Completed,Withdrawn'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::table('fitness_challenge_participants')->updateOrInsert(
            [
                'business_id' => $this->businessId(),
                'fitness_challenge_id' => $data['fitness_challenge_id'],
                'fitness_member_id' => $data['fitness_member_id'],
            ],
            $this->payload($data)
        );

        return back()->with('status', 'Challenge participant saved.');
    }

    public function equipment(FitnessFeatureGate $gate)
    {
        $gate->authorize('equipment');

        return $this->view('equipment', [
            'equipment' => $this->scoped('fitness_equipment')->latest()->paginate(20),
            'maintenanceLogs' => $this->equipmentMaintenanceQuery()->limit(40)->get(),
        ]);
    }

    public function storeEquipment(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('equipment');

        $data = $request->validate([
            'equipment_code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
            'warranty_expires_at' => ['nullable', 'date'],
            'status' => ['required', 'in:Active,Maintenance,Retired'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $data['equipment_code'] = $data['equipment_code'] ?: $this->nextCode('fitness_equipment', 'equipment_code', 'EQP');
        $data['cost'] = $data['cost'] ?? 0;
        DB::table('fitness_equipment')->insert($this->payload($data));

        return back()->with('status', 'Equipment saved.');
    }

    public function storeMaintenance(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('equipment');

        $data = $request->validate([
            'fitness_equipment_id' => ['required', Rule::exists('fitness_equipment', 'id')->where('business_id', $this->businessId())],
            'service_date' => ['required', 'date'],
            'next_service_date' => ['nullable', 'date', 'after_or_equal:service_date'],
            'technician' => ['nullable', 'string', 'max:255'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        DB::table('fitness_equipment_maintenance')->insert($this->payload($data));
        DB::table('fitness_equipment')->where('id', $data['fitness_equipment_id'])->update(['status' => 'Active', 'updated_at' => now()]);

        return back()->with('status', 'Maintenance recorded.');
    }

    public function reports(FitnessFeatureGate $gate)
    {
        $gate->authorize('reports');

        $today = now()->toDateString();

        return $this->view('reports', [
            'reportMetrics' => [
                'Active Members' => $this->scoped('fitness_members')->where('status', 'Active')->count(),
                'Expired Members' => $this->scoped('fitness_members')->where('status', 'Expired')->count(),
                'Renewals Due 7 Days' => $this->scoped('fitness_member_memberships')->where('status', 'Active')->whereBetween('ends_at', [$today, now()->addDays(7)->toDateString()])->count(),
                'Membership Revenue MTD' => Payment::whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()])->where('payable_type', 'like', '%Fitness%')->sum('amount'),
                'PT Sessions Today' => $this->scoped('fitness_pt_sessions')->whereDate('scheduled_at', $today)->count(),
                'Classes Today' => $this->scoped('fitness_class_sessions')->whereDate('starts_at', $today)->count(),
                'Maintenance Due' => $this->scoped('fitness_equipment_maintenance')->whereDate('next_service_date', '<=', $today)->count(),
            ],
            'topTrainers' => $this->topTrainers(),
            'bodyTrends' => $this->assessmentQuery()->limit(12)->get(),
        ]);
    }

    private function view(string $section, array $data = [])
    {
        return view('fitness.operations', array_merge($this->baseData(), ['section' => $section], $data));
    }

    private function baseData(): array
    {
        return [
            'members' => Member::with('client')->orderBy('member_number')->limit(500)->get(),
            'staffUsers' => $this->activeBusinessUsers()->where('is_active', true)->orderBy('name')->get(),
            'trainers' => $this->trainerQuery()->limit(500)->get(),
            'classTypes' => DB::table('fitness_class_types')->where(function ($query) {
                $query->whereNull('business_id')->orWhere('business_id', $this->businessId());
            })->where('is_active', true)->orderBy('name')->get(),
            'classOptions' => $this->scoped('fitness_classes')->where('status', 'Active')->orderBy('name')->get(),
            'sessionOptions' => $this->classSessionsQuery()->where('fitness_class_sessions.status', 'Scheduled')->limit(200)->get(),
            'programOptions' => $this->scoped('fitness_programs')->where('status', 'Active')->orderBy('name')->get(),
            'nutritionOptions' => $this->scoped('fitness_nutrition_plans')->where('status', 'Active')->orderBy('name')->get(),
            'challengeOptions' => $this->scoped('fitness_challenges')->where('status', 'Active')->orderBy('name')->get(),
            'equipmentOptions' => $this->scoped('fitness_equipment')->orderBy('name')->get(),
        ];
    }

    private function trainerQuery()
    {
        return DB::table('fitness_trainer_profiles')
            ->join('users', 'users.id', '=', 'fitness_trainer_profiles.user_id')
            ->where('fitness_trainer_profiles.business_id', $this->businessId())
            ->select('fitness_trainer_profiles.*', 'users.name as trainer_name', 'users.email as trainer_email')
            ->orderBy('users.name');
    }

    private function attendanceQuery()
    {
        return DB::table('fitness_attendance_logs')
            ->leftJoin('fitness_members', 'fitness_members.id', '=', 'fitness_attendance_logs.fitness_member_id')
            ->leftJoin('clients', 'clients.id', '=', 'fitness_members.client_id')
            ->where('fitness_attendance_logs.business_id', $this->businessId())
            ->select('fitness_attendance_logs.*', 'fitness_members.member_number', 'clients.name as member_name')
            ->latest('fitness_attendance_logs.entry_time');
    }

    private function classesQuery()
    {
        return DB::table('fitness_classes')
            ->leftJoin('fitness_class_types', 'fitness_class_types.id', '=', 'fitness_classes.fitness_class_type_id')
            ->leftJoin('users', 'users.id', '=', 'fitness_classes.trainer_id')
            ->where('fitness_classes.business_id', $this->businessId())
            ->select('fitness_classes.*', 'fitness_class_types.name as class_type', 'users.name as trainer_name')
            ->latest('fitness_classes.id');
    }

    private function classSessionsQuery()
    {
        return DB::table('fitness_class_sessions')
            ->join('fitness_classes', 'fitness_classes.id', '=', 'fitness_class_sessions.fitness_class_id')
            ->leftJoin('users', 'users.id', '=', 'fitness_class_sessions.trainer_id')
            ->where('fitness_class_sessions.business_id', $this->businessId())
            ->select('fitness_class_sessions.*', 'fitness_classes.name as class_name', 'fitness_classes.room', 'users.name as trainer_name')
            ->orderBy('fitness_class_sessions.starts_at');
    }

    private function classBookingsQuery()
    {
        return DB::table('fitness_class_bookings')
            ->join('fitness_class_sessions', 'fitness_class_sessions.id', '=', 'fitness_class_bookings.fitness_class_session_id')
            ->join('fitness_classes', 'fitness_classes.id', '=', 'fitness_class_sessions.fitness_class_id')
            ->join('fitness_members', 'fitness_members.id', '=', 'fitness_class_bookings.fitness_member_id')
            ->join('clients', 'clients.id', '=', 'fitness_members.client_id')
            ->where('fitness_class_bookings.business_id', $this->businessId())
            ->select('fitness_class_bookings.*', 'fitness_classes.name as class_name', 'fitness_class_sessions.starts_at', 'fitness_members.member_number', 'clients.name as member_name')
            ->latest('fitness_class_bookings.id');
    }

    private function eligibleMembershipsQuery()
    {
        return DB::table('fitness_member_memberships')
            ->join('fitness_members', 'fitness_members.id', '=', 'fitness_member_memberships.fitness_member_id')
            ->join('clients', 'clients.id', '=', 'fitness_members.client_id')
            ->join('fitness_membership_plans', 'fitness_membership_plans.id', '=', 'fitness_member_memberships.fitness_membership_plan_id')
            ->where('fitness_member_memberships.business_id', $this->businessId())
            ->where('fitness_member_memberships.status', 'Active')
            ->where(function ($query) {
                $query->whereNull('fitness_member_memberships.ends_at')->orWhereDate('fitness_member_memberships.ends_at', '>=', now()->toDateString());
            })
            ->select(
                'fitness_member_memberships.*',
                'fitness_members.member_number',
                'fitness_members.qr_code',
                'fitness_members.status as member_status',
                'clients.name as member_name',
                'fitness_membership_plans.name as plan_name'
            )
            ->latest('fitness_member_memberships.id');
    }

    private function recentMembershipsQuery()
    {
        return DB::table('fitness_member_memberships')
            ->join('fitness_members', 'fitness_members.id', '=', 'fitness_member_memberships.fitness_member_id')
            ->join('clients', 'clients.id', '=', 'fitness_members.client_id')
            ->join('fitness_membership_plans', 'fitness_membership_plans.id', '=', 'fitness_member_memberships.fitness_membership_plan_id')
            ->where('fitness_member_memberships.business_id', $this->businessId())
            ->select(
                'fitness_member_memberships.*',
                'fitness_members.member_number',
                'fitness_members.qr_code',
                'clients.name as member_name',
                'fitness_membership_plans.name as plan_name'
            )
            ->latest('fitness_member_memberships.id');
    }

    private function programsQuery()
    {
        return DB::table('fitness_programs')
            ->leftJoin('users', 'users.id', '=', 'fitness_programs.trainer_id')
            ->where('fitness_programs.business_id', $this->businessId())
            ->select('fitness_programs.*', 'users.name as trainer_name')
            ->latest('fitness_programs.id');
    }

    private function programAssignmentsQuery()
    {
        return DB::table('fitness_program_assignments')
            ->join('fitness_programs', 'fitness_programs.id', '=', 'fitness_program_assignments.fitness_program_id')
            ->join('fitness_members', 'fitness_members.id', '=', 'fitness_program_assignments.fitness_member_id')
            ->join('clients', 'clients.id', '=', 'fitness_members.client_id')
            ->leftJoin('users', 'users.id', '=', 'fitness_program_assignments.trainer_id')
            ->where('fitness_program_assignments.business_id', $this->businessId())
            ->select('fitness_program_assignments.*', 'fitness_programs.name as program_name', 'fitness_members.member_number', 'clients.name as member_name', 'users.name as trainer_name')
            ->latest('fitness_program_assignments.id');
    }

    private function exerciseQuery()
    {
        return DB::table('fitness_exercises')
            ->where(function ($query) {
                $query->whereNull('business_id')->orWhere('business_id', $this->businessId());
            })
            ->orderBy('category')
            ->orderBy('name');
    }

    private function healthQuery()
    {
        return DB::table('fitness_health_profiles')
            ->join('fitness_members', 'fitness_members.id', '=', 'fitness_health_profiles.fitness_member_id')
            ->join('clients', 'clients.id', '=', 'fitness_members.client_id')
            ->where('fitness_health_profiles.business_id', $this->businessId())
            ->select('fitness_health_profiles.*', 'fitness_members.member_number', 'clients.name as member_name')
            ->latest('fitness_health_profiles.id');
    }

    private function assessmentQuery()
    {
        return DB::table('fitness_assessments')
            ->join('fitness_members', 'fitness_members.id', '=', 'fitness_assessments.fitness_member_id')
            ->join('clients', 'clients.id', '=', 'fitness_members.client_id')
            ->leftJoin('users', 'users.id', '=', 'fitness_assessments.trainer_id')
            ->where('fitness_assessments.business_id', $this->businessId())
            ->select('fitness_assessments.*', 'fitness_members.member_number', 'clients.name as member_name', 'users.name as trainer_name')
            ->latest('fitness_assessments.assessment_date');
    }

    private function ptSessionsQuery()
    {
        return DB::table('fitness_pt_sessions')
            ->join('fitness_members', 'fitness_members.id', '=', 'fitness_pt_sessions.fitness_member_id')
            ->join('clients', 'clients.id', '=', 'fitness_members.client_id')
            ->join('users', 'users.id', '=', 'fitness_pt_sessions.trainer_id')
            ->leftJoin('fitness_pt_packages', 'fitness_pt_packages.id', '=', 'fitness_pt_sessions.fitness_pt_package_id')
            ->where('fitness_pt_sessions.business_id', $this->businessId())
            ->select('fitness_pt_sessions.*', 'fitness_members.member_number', 'clients.name as member_name', 'users.name as trainer_name', 'fitness_pt_packages.name as package_name')
            ->latest('fitness_pt_sessions.scheduled_at');
    }

    private function nutritionAssignmentsQuery()
    {
        return DB::table('fitness_nutrition_assignments')
            ->join('fitness_nutrition_plans', 'fitness_nutrition_plans.id', '=', 'fitness_nutrition_assignments.fitness_nutrition_plan_id')
            ->join('fitness_members', 'fitness_members.id', '=', 'fitness_nutrition_assignments.fitness_member_id')
            ->join('clients', 'clients.id', '=', 'fitness_members.client_id')
            ->leftJoin('users', 'users.id', '=', 'fitness_nutrition_assignments.trainer_id')
            ->where('fitness_nutrition_assignments.business_id', $this->businessId())
            ->select('fitness_nutrition_assignments.*', 'fitness_nutrition_plans.name as plan_name', 'fitness_members.member_number', 'clients.name as member_name', 'users.name as trainer_name')
            ->latest('fitness_nutrition_assignments.id');
    }

    private function nutritionAssignmentDownloadQuery()
    {
        return DB::table('fitness_nutrition_assignments')
            ->join('fitness_nutrition_plans', 'fitness_nutrition_plans.id', '=', 'fitness_nutrition_assignments.fitness_nutrition_plan_id')
            ->join('fitness_members', 'fitness_members.id', '=', 'fitness_nutrition_assignments.fitness_member_id')
            ->join('clients', 'clients.id', '=', 'fitness_members.client_id')
            ->leftJoin('users as assignment_trainers', 'assignment_trainers.id', '=', 'fitness_nutrition_assignments.trainer_id')
            ->leftJoin('users as plan_trainers', 'plan_trainers.id', '=', 'fitness_nutrition_plans.trainer_id')
            ->where('fitness_nutrition_assignments.business_id', $this->businessId())
            ->select(
                'fitness_nutrition_assignments.*',
                'fitness_nutrition_plans.name as plan_name',
                'fitness_nutrition_plans.calories',
                'fitness_nutrition_plans.protein',
                'fitness_nutrition_plans.carbohydrates',
                'fitness_nutrition_plans.fat',
                'fitness_nutrition_plans.fiber',
                'fitness_nutrition_plans.water_intake_goal',
                'fitness_nutrition_plans.meals',
                'fitness_nutrition_plans.description',
                'fitness_members.member_number',
                'fitness_members.emergency_contact_name',
                'fitness_members.emergency_contact_phone',
                'clients.name as member_name',
                'clients.email as member_email',
                'clients.phone as member_phone',
                DB::raw('coalesce(assignment_trainers.name, plan_trainers.name) as trainer_name')
            );
    }

    private function challengeParticipantsQuery()
    {
        return DB::table('fitness_challenge_participants')
            ->join('fitness_challenges', 'fitness_challenges.id', '=', 'fitness_challenge_participants.fitness_challenge_id')
            ->join('fitness_members', 'fitness_members.id', '=', 'fitness_challenge_participants.fitness_member_id')
            ->join('clients', 'clients.id', '=', 'fitness_members.client_id')
            ->where('fitness_challenge_participants.business_id', $this->businessId())
            ->select('fitness_challenge_participants.*', 'fitness_challenges.name as challenge_name', 'fitness_members.member_number', 'clients.name as member_name')
            ->orderByRaw('fitness_challenge_participants.rank is null, fitness_challenge_participants.rank asc')
            ->latest('fitness_challenge_participants.id');
    }

    private function equipmentMaintenanceQuery()
    {
        return DB::table('fitness_equipment_maintenance')
            ->join('fitness_equipment', 'fitness_equipment.id', '=', 'fitness_equipment_maintenance.fitness_equipment_id')
            ->where('fitness_equipment_maintenance.business_id', $this->businessId())
            ->select('fitness_equipment_maintenance.*', 'fitness_equipment.name as equipment_name', 'fitness_equipment.equipment_code')
            ->latest('fitness_equipment_maintenance.service_date');
    }

    private function assertNoClassConflict(array $data, object $class): void
    {
        $overlap = function ($query) use ($data) {
            $query->where('starts_at', '<', $data['ends_at'])->where('ends_at', '>', $data['starts_at']);
        };

        if (! empty($data['trainer_id'])) {
            $trainerConflict = $this->scoped('fitness_class_sessions')
                ->where('trainer_id', $data['trainer_id'])
                ->where('status', 'Scheduled')
                ->where($overlap)
                ->exists();

            if ($trainerConflict) {
                throw ValidationException::withMessages(['trainer_id' => 'This trainer already has a class at that time.']);
            }
        }

        if (! empty($class->room)) {
            $roomConflict = DB::table('fitness_class_sessions')
                ->join('fitness_classes', 'fitness_classes.id', '=', 'fitness_class_sessions.fitness_class_id')
                ->where('fitness_class_sessions.business_id', $this->businessId())
                ->where('fitness_classes.room', $class->room)
                ->where('fitness_class_sessions.status', 'Scheduled')
                ->where($overlap)
                ->exists();

            if ($roomConflict) {
                throw ValidationException::withMessages(['starts_at' => 'This room already has a class at that time.']);
            }
        }
    }

    private function assertNoPtConflict(array $data): void
    {
        $start = Carbon::parse($data['scheduled_at']);
        $end = $start->copy()->addMinutes((int) $data['duration_minutes']);

        $sessions = $this->scoped('fitness_pt_sessions')
            ->where('trainer_id', $data['trainer_id'])
            ->where('status', 'Scheduled')
            ->where('scheduled_at', '<', $end)
            ->get(['scheduled_at', 'duration_minutes']);

        $conflict = $sessions->contains(function ($session) use ($start) {
            return Carbon::parse($session->scheduled_at)->addMinutes((int) $session->duration_minutes)->greaterThan($start);
        });

        if ($conflict) {
            throw ValidationException::withMessages(['scheduled_at' => 'This trainer already has a PT session at that time.']);
        }
    }

    private function assertActiveMember(int $memberId): void
    {
        $active = $this->scoped('fitness_member_memberships')
            ->where('fitness_member_id', $memberId)
            ->where('status', 'Active')
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', now()->toDateString());
            })
            ->exists();

        if (! $active) {
            throw ValidationException::withMessages(['fitness_member_id' => 'This member does not have an active membership.']);
        }
    }

    private function promoteWaitlist(int $sessionId): void
    {
        $next = $this->scoped('fitness_class_bookings')
            ->where('fitness_class_session_id', $sessionId)
            ->where('status', 'Waitlisted')
            ->orderBy('booked_at')
            ->first();

        if ($next) {
            DB::table('fitness_class_bookings')->where('id', $next->id)->update(['status' => 'Booked', 'updated_at' => now()]);
        }
    }

    private function findMember(string $identifier): ?object
    {
        $identifier = trim($identifier);

        $member = $this->scoped('fitness_members')
            ->where(function ($query) use ($identifier) {
                $query->where('member_number', $identifier)
                    ->orWhere('qr_code', $identifier)
                    ->orWhere('id', ctype_digit($identifier) ? (int) $identifier : 0);
            })
            ->first();

        if ($member) {
            return $member;
        }

        $membership = DB::table('fitness_member_memberships')
            ->join('fitness_members', 'fitness_members.id', '=', 'fitness_member_memberships.fitness_member_id')
            ->where('fitness_member_memberships.business_id', $this->businessId())
            ->where('fitness_member_memberships.membership_number', $identifier)
            ->select('fitness_members.*')
            ->first();

        return $membership ?: null;
    }

    private function membershipAccessMessage(int $memberId): string
    {
        $membership = DB::table('fitness_member_memberships')
            ->leftJoin('fitness_membership_plans', 'fitness_membership_plans.id', '=', 'fitness_member_memberships.fitness_membership_plan_id')
            ->where('fitness_member_memberships.business_id', $this->businessId())
            ->where('fitness_member_memberships.fitness_member_id', $memberId)
            ->select('fitness_member_memberships.*', 'fitness_membership_plans.name as plan_name')
            ->latest('fitness_member_memberships.id')
            ->first();

        if (! $membership) {
            return 'No membership enrollment was found for this member. Enroll the member on the Members tab before check-in.';
        }

        if ($membership->status !== 'Active') {
            return 'Latest membership '.$membership->membership_number.' is '.$membership->status.'. Renew or activate it before check-in.';
        }

        if ($membership->ends_at && Carbon::parse($membership->ends_at)->lt(now()->startOfDay())) {
            return 'Latest membership '.$membership->membership_number.' expired on '.Carbon::parse($membership->ends_at)->format('d M Y').'. Renew it before check-in.';
        }

        return 'This member does not have an active membership that is valid today.';
    }

    private function topTrainers()
    {
        return DB::table('fitness_pt_sessions')
            ->join('users', 'users.id', '=', 'fitness_pt_sessions.trainer_id')
            ->where('fitness_pt_sessions.business_id', $this->businessId())
            ->where('fitness_pt_sessions.status', 'Completed')
            ->select('users.name', DB::raw('count(*) as sessions_completed'))
            ->groupBy('users.name')
            ->orderByDesc('sessions_completed')
            ->limit(5)
            ->get();
    }

    private function fitnessSettings(): object
    {
        $settings = DB::table('fitness_settings')->where('business_id', $this->businessId())->first();
        if ($settings) {
            return $settings;
        }

        DB::table('fitness_settings')->insert([
            'tenant_id' => ActiveTenant::id(),
            'business_id' => $this->businessId(),
            'capacity_limit' => 100,
            'class_cancellation_window_hours' => 4,
            'auto_promote_waitlist' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('fitness_settings')->where('business_id', $this->businessId())->first();
    }

    private function scoped(string $table)
    {
        return DB::table($table)->where($table.'.business_id', $this->businessId());
    }

    private function payload(array $data): array
    {
        return array_merge($data, [
            'tenant_id' => ActiveTenant::id(),
            'business_id' => $this->businessId(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function businessId(): int
    {
        return (int) ActiveBusiness::id();
    }

    private function nextCode(string $table, string $column, string $prefix): string
    {
        $next = $this->scoped($table)->count() + 1;

        do {
            $code = $prefix.'-'.now()->format('Y').'-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while ($this->scoped($table)->where($column, $code)->exists());

        return $code;
    }

    private function linesToJson(?string $value): ?string
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value))));

        return $lines ? json_encode($lines) : null;
    }
}
