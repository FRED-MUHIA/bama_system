<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->createSettingsTable();
        $this->createTrainerTables();
        $this->createAttendanceTables();
        $this->createClassTables();
        $this->createProgramAndHealthTables();
        $this->createNutritionChallengeAndEquipmentTables();
        $this->seedReferenceData();
        $this->registerOperations();
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_equipment_maintenance');
        Schema::dropIfExists('fitness_equipment');
        Schema::dropIfExists('fitness_challenge_participants');
        Schema::dropIfExists('fitness_challenges');
        Schema::dropIfExists('fitness_nutrition_assignments');
        Schema::dropIfExists('fitness_nutrition_plans');
        Schema::dropIfExists('fitness_pt_sessions');
        Schema::dropIfExists('fitness_pt_packages');
        Schema::dropIfExists('fitness_assessments');
        Schema::dropIfExists('fitness_health_profiles');
        Schema::dropIfExists('fitness_program_assignments');
        Schema::dropIfExists('fitness_programs');
        Schema::dropIfExists('fitness_exercises');
        Schema::dropIfExists('fitness_class_bookings');
        Schema::dropIfExists('fitness_class_sessions');
        Schema::dropIfExists('fitness_classes');
        Schema::dropIfExists('fitness_class_types');
        Schema::dropIfExists('fitness_attendance_logs');
        Schema::dropIfExists('fitness_trainer_member');
        Schema::dropIfExists('fitness_trainer_blackouts');
        Schema::dropIfExists('fitness_trainer_profiles');
        Schema::dropIfExists('fitness_settings');
    }

    private function createSettingsTable(): void
    {
        if (! Schema::hasTable('fitness_settings')) {
            Schema::create('fitness_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('capacity_limit')->default(100);
                $table->unsignedInteger('class_cancellation_window_hours')->default(4);
                $table->boolean('auto_promote_waitlist')->default(true);
                $table->timestamps();

                $table->unique('business_id', 'fit_settings_business_unique');
            });
        }
    }

    private function createTrainerTables(): void
    {
        if (! Schema::hasTable('fitness_trainer_profiles')) {
            Schema::create('fitness_trainer_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('trainer_code');
                $table->string('photo_path')->nullable();
                $table->text('specialization')->nullable();
                $table->json('certifications')->nullable();
                $table->unsignedInteger('experience_years')->default(0);
                $table->json('availability')->nullable();
                $table->decimal('hourly_rate', 14, 2)->default(0);
                $table->decimal('commission_percent', 5, 2)->default(0);
                $table->unsignedInteger('max_clients')->nullable();
                $table->decimal('rating', 3, 2)->nullable();
                $table->string('status')->default('Active');
                $table->text('bio')->nullable();
                $table->timestamps();

                $table->unique(['business_id', 'user_id'], 'fit_trainer_business_user_unique');
                $table->unique(['business_id', 'trainer_code'], 'fit_trainer_business_code_unique');
                $table->index(['tenant_id', 'business_id'], 'fit_trainer_tenant_business_idx');
                $table->index(['business_id', 'status'], 'fit_trainer_business_status_idx');
            });
        }

        if (! Schema::hasTable('fitness_trainer_blackouts')) {
            Schema::create('fitness_trainer_blackouts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
                $table->dateTime('starts_at');
                $table->dateTime('ends_at');
                $table->string('reason')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'trainer_id', 'starts_at'], 'fit_trainer_blackout_lookup_idx');
            });
        }

        if (! Schema::hasTable('fitness_trainer_member')) {
            Schema::create('fitness_trainer_member', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('fitness_member_id')->constrained('fitness_members')->cascadeOnDelete();
                $table->boolean('is_active')->default(true);
                $table->date('assigned_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['business_id', 'trainer_id', 'fitness_member_id'], 'fit_trainer_member_unique');
                $table->index(['business_id', 'is_active'], 'fit_trainer_member_active_idx');
            });
        }
    }

    private function createAttendanceTables(): void
    {
        if (! Schema::hasTable('fitness_attendance_logs')) {
            Schema::create('fitness_attendance_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('fitness_member_id')->nullable()->constrained('fitness_members')->nullOnDelete();
                $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('person_type')->default('Member');
                $table->string('method')->default('Manual');
                $table->dateTime('entry_time');
                $table->dateTime('exit_time')->nullable();
                $table->unsignedInteger('visit_minutes')->nullable();
                $table->string('status')->default('In Gym');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'entry_time'], 'fit_attendance_entry_idx');
                $table->index(['business_id', 'status'], 'fit_attendance_status_idx');
                $table->index(['business_id', 'fitness_member_id', 'exit_time'], 'fit_attendance_member_open_idx');
            });
        }
    }

    private function createClassTables(): void
    {
        if (! Schema::hasTable('fitness_class_types')) {
            Schema::create('fitness_class_types', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['business_id', 'name'], 'fit_class_types_business_name_unique');
                $table->index(['business_id', 'is_active'], 'fit_class_types_business_active_idx');
            });
        }

        if (! Schema::hasTable('fitness_classes')) {
            Schema::create('fitness_classes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('fitness_class_type_id')->nullable()->constrained('fitness_class_types')->nullOnDelete();
                $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->string('room')->nullable();
                $table->string('level')->nullable();
                $table->unsignedInteger('capacity')->default(1);
                $table->unsignedInteger('duration_minutes')->default(60);
                $table->decimal('drop_in_price', 14, 2)->default(0);
                $table->json('eligible_plan_ids')->nullable();
                $table->string('status')->default('Active');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'status'], 'fit_classes_business_status_idx');
                $table->index(['business_id', 'trainer_id'], 'fit_classes_business_trainer_idx');
            });
        }

        if (! Schema::hasTable('fitness_class_sessions')) {
            Schema::create('fitness_class_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('fitness_class_id')->constrained('fitness_classes')->cascadeOnDelete();
                $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('starts_at');
                $table->dateTime('ends_at');
                $table->unsignedInteger('capacity')->default(1);
                $table->string('status')->default('Scheduled');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'starts_at'], 'fit_class_sessions_start_idx');
                $table->index(['business_id', 'trainer_id', 'starts_at'], 'fit_class_sessions_trainer_idx');
                $table->index(['business_id', 'status'], 'fit_class_sessions_status_idx');
            });
        }

        if (! Schema::hasTable('fitness_class_bookings')) {
            Schema::create('fitness_class_bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('fitness_class_session_id')->constrained('fitness_class_sessions')->cascadeOnDelete();
                $table->foreignId('fitness_member_id')->constrained('fitness_members')->cascadeOnDelete();
                $table->string('status')->default('Booked');
                $table->timestamp('booked_at')->nullable();
                $table->timestamp('attended_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['business_id', 'fitness_class_session_id', 'fitness_member_id'], 'fit_class_booking_unique');
                $table->index(['business_id', 'status'], 'fit_class_booking_status_idx');
            });
        }
    }

    private function createProgramAndHealthTables(): void
    {
        if (! Schema::hasTable('fitness_exercises')) {
            Schema::create('fitness_exercises', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('category');
                $table->string('target_muscle')->nullable();
                $table->string('difficulty')->default('Beginner');
                $table->text('instructions')->nullable();
                $table->string('video_url')->nullable();
                $table->string('image_url')->nullable();
                $table->string('equipment_required')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['business_id', 'name'], 'fit_exercises_business_name_unique');
                $table->index(['business_id', 'category'], 'fit_exercises_business_category_idx');
            });
        }

        if (! Schema::hasTable('fitness_programs')) {
            Schema::create('fitness_programs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->string('program_type');
                $table->string('difficulty')->default('Beginner');
                $table->unsignedInteger('duration_weeks')->default(4);
                $table->decimal('price', 14, 2)->default(0);
                $table->boolean('is_public')->default(false);
                $table->json('structure')->nullable();
                $table->string('status')->default('Active');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'status'], 'fit_programs_business_status_idx');
                $table->index(['business_id', 'program_type'], 'fit_programs_business_type_idx');
            });
        }

        if (! Schema::hasTable('fitness_program_assignments')) {
            Schema::create('fitness_program_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('fitness_program_id')->constrained('fitness_programs')->cascadeOnDelete();
                $table->foreignId('fitness_member_id')->constrained('fitness_members')->cascadeOnDelete();
                $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('starts_at');
                $table->date('ends_at')->nullable();
                $table->string('status')->default('Active');
                $table->decimal('adherence_percent', 5, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'status'], 'fit_program_assign_status_idx');
                $table->index(['business_id', 'fitness_member_id'], 'fit_program_assign_member_idx');
            });
        }

        if (! Schema::hasTable('fitness_health_profiles')) {
            Schema::create('fitness_health_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('fitness_member_id')->constrained('fitness_members')->cascadeOnDelete();
                $table->decimal('height_cm', 6, 2)->nullable();
                $table->decimal('weight_kg', 6, 2)->nullable();
                $table->decimal('bmi', 6, 2)->nullable();
                $table->decimal('body_fat_percentage', 5, 2)->nullable();
                $table->decimal('muscle_mass', 6, 2)->nullable();
                $table->string('blood_pressure')->nullable();
                $table->unsignedInteger('resting_heart_rate')->nullable();
                $table->text('allergies')->nullable();
                $table->text('medical_conditions')->nullable();
                $table->text('injuries')->nullable();
                $table->json('goals')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['business_id', 'fitness_member_id'], 'fit_health_profile_member_unique');
                $table->index(['tenant_id', 'business_id'], 'fit_health_profile_tenant_business_idx');
            });
        }

        if (! Schema::hasTable('fitness_assessments')) {
            Schema::create('fitness_assessments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('fitness_member_id')->constrained('fitness_members')->cascadeOnDelete();
                $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('assessment_date');
                $table->decimal('weight_kg', 6, 2)->nullable();
                $table->decimal('bmi', 6, 2)->nullable();
                $table->decimal('body_fat_percentage', 5, 2)->nullable();
                $table->decimal('muscle_mass', 6, 2)->nullable();
                $table->unsignedInteger('fitness_score')->nullable();
                $table->unsignedInteger('strength_score')->nullable();
                $table->unsignedInteger('cardio_score')->nullable();
                $table->unsignedInteger('flexibility_score')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'assessment_date'], 'fit_assessments_date_idx');
                $table->index(['business_id', 'fitness_member_id'], 'fit_assessments_member_idx');
            });
        }
    }

    private function createNutritionChallengeAndEquipmentTables(): void
    {
        if (! Schema::hasTable('fitness_pt_packages')) {
            Schema::create('fitness_pt_packages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->unsignedInteger('sessions_included')->default(1);
                $table->decimal('price', 14, 2)->default(0);
                $table->unsignedInteger('validity_days')->default(30);
                $table->string('status')->default('Active');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->unique(['business_id', 'name'], 'fit_pt_packages_business_name_unique');
                $table->index(['business_id', 'status'], 'fit_pt_packages_status_idx');
            });
        }

        if (! Schema::hasTable('fitness_pt_sessions')) {
            Schema::create('fitness_pt_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('fitness_member_id')->constrained('fitness_members')->cascadeOnDelete();
                $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('fitness_pt_package_id')->nullable()->constrained('fitness_pt_packages')->nullOnDelete();
                $table->dateTime('scheduled_at');
                $table->unsignedInteger('duration_minutes')->default(60);
                $table->string('status')->default('Scheduled');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'scheduled_at'], 'fit_pt_sessions_schedule_idx');
                $table->index(['business_id', 'trainer_id', 'scheduled_at'], 'fit_pt_sessions_trainer_idx');
                $table->index(['business_id', 'status'], 'fit_pt_sessions_status_idx');
            });
        }

        if (! Schema::hasTable('fitness_nutrition_plans')) {
            Schema::create('fitness_nutrition_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->unsignedInteger('calories')->nullable();
                $table->unsignedInteger('protein')->nullable();
                $table->unsignedInteger('carbohydrates')->nullable();
                $table->unsignedInteger('fat')->nullable();
                $table->unsignedInteger('fiber')->nullable();
                $table->unsignedInteger('water_intake_goal')->nullable();
                $table->json('meals')->nullable();
                $table->string('status')->default('Active');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'status'], 'fit_nutrition_status_idx');
            });
        }

        if (! Schema::hasTable('fitness_nutrition_assignments')) {
            Schema::create('fitness_nutrition_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('fitness_nutrition_plan_id')->constrained('fitness_nutrition_plans')->cascadeOnDelete();
                $table->foreignId('fitness_member_id')->constrained('fitness_members')->cascadeOnDelete();
                $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('starts_at');
                $table->date('ends_at')->nullable();
                $table->decimal('compliance_percent', 5, 2)->default(0);
                $table->string('status')->default('Active');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'status'], 'fit_nutrition_assign_status_idx');
                $table->index(['business_id', 'fitness_member_id'], 'fit_nutrition_assign_member_idx');
            });
        }

        if (! Schema::hasTable('fitness_challenges')) {
            Schema::create('fitness_challenges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('challenge_type');
                $table->date('starts_at');
                $table->date('ends_at');
                $table->string('reward')->nullable();
                $table->string('status')->default('Active');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'status'], 'fit_challenges_status_idx');
                $table->index(['business_id', 'starts_at', 'ends_at'], 'fit_challenges_dates_idx');
            });
        }

        if (! Schema::hasTable('fitness_challenge_participants')) {
            Schema::create('fitness_challenge_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('fitness_challenge_id')->constrained('fitness_challenges')->cascadeOnDelete();
                $table->foreignId('fitness_member_id')->constrained('fitness_members')->cascadeOnDelete();
                $table->decimal('progress_value', 10, 2)->default(0);
                $table->unsignedInteger('rank')->nullable();
                $table->string('status')->default('Active');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['business_id', 'fitness_challenge_id', 'fitness_member_id'], 'fit_challenge_participant_unique');
                $table->index(['business_id', 'rank'], 'fit_challenge_participant_rank_idx');
            });
        }

        if (! Schema::hasTable('fitness_equipment')) {
            Schema::create('fitness_equipment', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('equipment_code');
                $table->string('name');
                $table->string('category')->nullable();
                $table->string('brand')->nullable();
                $table->string('model')->nullable();
                $table->string('serial_number')->nullable();
                $table->date('purchase_date')->nullable();
                $table->date('warranty_expires_at')->nullable();
                $table->string('status')->default('Active');
                $table->string('location')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['business_id', 'equipment_code'], 'fit_equipment_business_code_unique');
                $table->index(['business_id', 'status'], 'fit_equipment_status_idx');
                $table->index(['business_id', 'warranty_expires_at'], 'fit_equipment_warranty_idx');
            });
        }

        if (! Schema::hasTable('fitness_equipment_maintenance')) {
            Schema::create('fitness_equipment_maintenance', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('fitness_equipment_id')->constrained('fitness_equipment')->cascadeOnDelete();
                $table->date('service_date');
                $table->date('next_service_date')->nullable();
                $table->string('technician')->nullable();
                $table->decimal('cost', 14, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'service_date'], 'fit_equipment_maint_service_idx');
                $table->index(['business_id', 'next_service_date'], 'fit_equipment_maint_next_idx');
            });
        }
    }

    private function seedReferenceData(): void
    {
        $now = now();

        if (Schema::hasTable('fitness_class_types')) {
            foreach (['Yoga', 'Pilates', 'CrossFit', 'HIIT', 'Zumba', 'Cycling', 'Boxing', 'Strength Training'] as $name) {
                DB::table('fitness_class_types')->updateOrInsert(
                    ['business_id' => null, 'name' => $name],
                    ['is_active' => true, 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        if (Schema::hasTable('fitness_exercises')) {
            foreach ([
                ['Push Up', 'Chest', 'Chest', 'Beginner', 'Bodyweight'],
                ['Squat', 'Legs', 'Quadriceps', 'Beginner', 'Bodyweight or barbell'],
                ['Plank', 'Core', 'Core', 'Beginner', 'Mat'],
                ['Deadlift', 'Back', 'Posterior chain', 'Advanced', 'Barbell'],
                ['Shoulder Press', 'Shoulders', 'Deltoids', 'Intermediate', 'Dumbbells'],
                ['Treadmill Run', 'Cardio', 'Cardio', 'Beginner', 'Treadmill'],
                ['Biceps Curl', 'Arms', 'Biceps', 'Beginner', 'Dumbbells'],
                ['Hamstring Stretch', 'Flexibility', 'Hamstrings', 'Beginner', 'Mat'],
            ] as [$name, $category, $muscle, $difficulty, $equipment]) {
                DB::table('fitness_exercises')->updateOrInsert(
                    ['business_id' => null, 'name' => $name],
                    [
                        'category' => $category,
                        'target_muscle' => $muscle,
                        'difficulty' => $difficulty,
                        'equipment_required' => $equipment,
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }

    private function registerOperations(): void
    {
        $now = now();
        $permissions = [
            'fitness.trainers.view',
            'fitness.trainers.manage',
            'fitness.attendance.view',
            'fitness.attendance.manage',
            'fitness.classes.view',
            'fitness.classes.manage',
            'fitness.programs.view',
            'fitness.programs.manage',
            'fitness.exercises.view',
            'fitness.exercises.manage',
            'fitness.health.view',
            'fitness.health.manage',
            'fitness.assessments.view',
            'fitness.assessments.manage',
            'fitness.personal-training.view',
            'fitness.personal-training.manage',
            'fitness.nutrition.view',
            'fitness.nutrition.manage',
            'fitness.challenges.view',
            'fitness.challenges.manage',
            'fitness.equipment.view',
            'fitness.equipment.manage',
        ];

        if (Schema::hasTable('iam_permissions')) {
            foreach ($permissions as $permission) {
                DB::table('iam_permissions')->updateOrInsert(
                    ['name' => $permission],
                    ['module' => 'fitness', 'description' => Str::headline(str_replace(['.', '-'], ' ', $permission)), 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        if (Schema::hasTable('modules')) {
            $current = DB::table('modules')->where('slug', 'fitness')->value('permissions');
            $merged = array_values(array_unique(array_merge(
                $current ? json_decode($current, true) ?: [] : [],
                $permissions
            )));

            DB::table('modules')->where('slug', 'fitness')->update([
                'permissions' => json_encode($merged),
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable('plans') && Schema::hasTable('subscription_features')) {
            $features = [
                'fitness.trainers',
                'fitness.attendance',
                'fitness.classes',
                'fitness.programs',
                'fitness.exercises',
                'fitness.health',
                'fitness.assessments',
                'fitness.personal-training',
                'fitness.nutrition',
                'fitness.challenges',
                'fitness.equipment',
                'fitness.reports',
            ];

            foreach (DB::table('plans')->pluck('id') as $planId) {
                foreach ($features as $feature) {
                    DB::table('subscription_features')->updateOrInsert(
                        ['plan_id' => $planId, 'feature' => $feature],
                        ['limit' => null, 'value' => null, 'enabled' => true, 'updated_at' => $now, 'created_at' => $now]
                    );
                }
            }
        }
    }
};
