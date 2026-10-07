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
        $definitions = [
            'ngo_sectors' => function (Blueprint $table) {
                $this->scope($table);
                $table->string('name');
                $table->string('code')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['business_id', 'name']);
            },
            'ngo_donors' => function (Blueprint $table) {
                $this->scope($table);
                $table->string('donor_number')->nullable();
                $table->string('name');
                $table->string('organization')->nullable();
                $table->string('type')->default('Foundation');
                $table->string('country')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->text('address')->nullable();
                $table->string('website')->nullable();
                $table->text('notes')->nullable();
                $table->string('status')->default('Active')->index();
                $table->timestamps();
                $table->index(['business_id', 'name']);
            },
            'ngo_programs' => function (Blueprint $table) {
                $this->scope($table);
                $table->string('program_number')->nullable();
                $table->string('name');
                $table->string('code')->nullable();
                $table->text('description')->nullable();
                $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('ngo_sector_id')->nullable()->constrained('ngo_sectors')->nullOnDelete();
                $table->foreignId('donor_id')->nullable()->constrained('ngo_donors')->nullOnDelete();
                $table->string('funding_source')->nullable();
                $table->string('location')->nullable();
                $table->date('starts_on')->nullable();
                $table->date('ends_on')->nullable();
                $table->decimal('budget', 16, 2)->default(0);
                $table->string('currency', 3)->default('KES');
                $table->string('status')->default('Planning')->index();
                $table->timestamps();
                $table->index(['business_id', 'status']);
            },
            'ngo_activities' => function (Blueprint $table) {
                $this->scope($table);
                $table->foreignId('program_id')->nullable()->constrained('ngo_programs')->nullOnDelete();
                $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
                $table->string('name');
                $table->string('activity_type')->nullable();
                $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('location')->nullable();
                $table->date('starts_on')->nullable();
                $table->date('ends_on')->nullable();
                $table->decimal('budget', 16, 2)->default(0);
                $table->string('status')->default('Planned')->index();
                $table->text('description')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'program_id', 'status']);
                $table->index(['business_id', 'project_id', 'starts_on']);
            },
            'ngo_beneficiaries' => function (Blueprint $table) {
                $this->scope($table);
                $table->string('beneficiary_number')->nullable();
                $table->string('name');
                $table->string('type')->default('Individual')->index();
                $table->string('gender')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->string('phone')->nullable();
                $table->string('country')->nullable();
                $table->string('county')->nullable();
                $table->string('district')->nullable();
                $table->string('ward')->nullable();
                $table->string('community')->nullable();
                $table->string('village')->nullable();
                $table->json('household_information')->nullable();
                $table->json('vulnerability_information')->nullable();
                $table->json('custom_fields')->nullable();
                $table->date('registered_on')->nullable();
                $table->string('registration_status')->default('Registered')->index();
                $table->string('status')->default('Active')->index();
                $table->timestamps();
                $table->index(['business_id', 'name']);
                $table->index(['business_id', 'county', 'community']);
            },
            'ngo_program_beneficiaries' => function (Blueprint $table) {
                $this->scope($table);
                $table->foreignId('program_id')->constrained('ngo_programs')->cascadeOnDelete();
                $table->foreignId('beneficiary_id')->constrained('ngo_beneficiaries')->cascadeOnDelete();
                $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
                $table->date('enrolled_on')->nullable();
                $table->date('exited_on')->nullable();
                $table->string('status')->default('Enrolled')->index();
                $table->json('custom_fields')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'program_id', 'beneficiary_id'], 'ngo_program_beneficiary_unique');
            },
        ];

        foreach ($definitions as $tableName => $definition) {
            if (! Schema::hasTable($tableName)) {
                Schema::create($tableName, $definition);
            }
        }

        if (Schema::hasTable('businesses') && Schema::hasTable('tenants')) {
            $ngoBusinessIds = DB::table('businesses')->join('tenants', 'tenants.id', '=', 'businesses.tenant_id')
                ->whereIn('tenants.industry', ['ngo', 'NGO', 'NGO & Non-Profit'])->pluck('businesses.id');
            $defaultSectors = ['Health', 'Education', 'Agriculture', 'Water & Sanitation', 'Food Security', 'Environment', 'Climate', 'Child Protection', 'Gender', 'Youth', 'Livelihoods', 'Governance', 'Human Rights', 'Housing', 'Emergency Response', 'Economic Empowerment', 'ICT / Digital Inclusion', 'Research', 'Advocacy'];
            foreach ($ngoBusinessIds as $businessId) {
                foreach ($defaultSectors as $sector) {
                    DB::table('ngo_sectors')->insertOrIgnore([
                        'tenant_id' => DB::table('businesses')->where('id', $businessId)->value('tenant_id'),
                        'business_id' => $businessId,
                        'name' => $sector,
                        'code' => Str::upper(Str::slug($sector, '_')),
                        'is_active' => true,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        }

        if (Schema::hasTable('modules')) {
            $moduleId = DB::table('modules')->where('slug', 'ngo')->value('id');
            if (! $moduleId) {
                $moduleId = DB::table('modules')->insertGetId([
                    'name' => 'NGO & Non-Profit', 'slug' => 'ngo', 'type' => 'industry',
                    'description' => 'Programs, beneficiaries, donors, grants, and impact management.',
                    'is_core' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            if ($moduleId && Schema::hasTable('industry_modules')) {
                foreach (['ngo', 'NGO', 'NGO & Non-Profit'] as $industry) {
                    DB::table('industry_modules')->updateOrInsert(
                        ['industry' => $industry, 'module_id' => $moduleId],
                        ['enabled_by_default' => true, 'created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
            if ($moduleId && Schema::hasTable('tenants') && Schema::hasTable('tenant_modules')) {
                $tenantIds = DB::table('tenants')->whereIn('industry', ['ngo', 'NGO', 'NGO & Non-Profit'])->pluck('id');
                foreach ($tenantIds as $tenantId) {
                    DB::table('tenant_modules')->updateOrInsert(
                        ['tenant_id' => $tenantId, 'module_id' => $moduleId],
                        ['enabled' => true, 'enabled_at' => now(), 'created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
        }

        if (Schema::hasTable('iam_permissions')) {
            foreach ([
                'ngo.view', 'ngo.manage', 'ngo.reports', 'ngo.programs.view', 'ngo.programs.manage',
                'ngo.activities.view', 'ngo.activities.manage', 'ngo.beneficiaries.view', 'ngo.beneficiaries.manage',
                'ngo.beneficiaries.sensitive.view', 'ngo.donors.view', 'ngo.donors.manage', 'ngo.grants.view',
                'ngo.grants.manage', 'ngo.funds.view', 'ngo.funds.manage', 'ngo.monitoring.view', 'ngo.monitoring.manage',
                'ngo.safeguarding.view', 'ngo.safeguarding.manage', 'ngo.impact.reporting.view',
            ] as $permission) {
                DB::table('iam_permissions')->updateOrInsert(
                    ['name' => $permission],
                    ['module' => 'ngo', 'description' => Str::headline(str_replace(['ngo.', '.'], ['', ' '], $permission)), 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        foreach (['ngo_program_beneficiaries', 'ngo_beneficiaries', 'ngo_activities', 'ngo_programs', 'ngo_donors', 'ngo_sectors'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function scope(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
        $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
    }
};
