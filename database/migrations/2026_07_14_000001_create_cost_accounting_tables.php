<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['business_id', 'code']);
        });

        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('name');
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->boolean('is_project')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['business_id', 'code']);
            $table->unique('project_id');
        });

        Schema::create('accounting_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('name');
            $table->unsignedSmallInteger('fiscal_year');
            $table->decimal('amount', 15, 2);
            $table->decimal('alert_threshold', 5, 2)->default(80);
            $table->string('status')->default('Draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('accounting_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_type');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('direction', 20);
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->decimal('amount', 15, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['transaction_type', 'transaction_id']);
        });

        Schema::create('accounting_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['auditable_type', 'auditable_id']);
        });

        if (! Schema::hasColumn('projects', 'cost_center_id')) {
            Schema::table('projects', fn (Blueprint $table) => $table->unsignedBigInteger('cost_center_id')->nullable()->after('site_id')->index());
        }
        if (! Schema::hasColumn('businesses', 'industry')) {
            Schema::table('businesses', fn (Blueprint $table) => $table->string('industry')->nullable()->after('name'));
        }

        foreach (DB::table('projects')->select('id', 'business_id', 'project_name')->orderBy('id')->get() as $project) {
            $departmentId = DB::table('departments')->where('business_id', $project->business_id)->where('code', 'PROJECTS')->value('id');
            if (! $departmentId) {
                $departmentId = DB::table('departments')->insertGetId(['business_id' => $project->business_id, 'name' => 'Projects', 'code' => 'PROJECTS', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
            $centerId = DB::table('cost_centers')->where('project_id', $project->id)->value('id');
            if (! $centerId) {
                $centerId = DB::table('cost_centers')->insertGetId(['business_id' => $project->business_id, 'department_id' => $departmentId, 'project_id' => $project->id, 'name' => $project->project_name, 'code' => 'PRJ-'.$project->id, 'is_project' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('projects')->where('id', $project->id)->update(['cost_center_id' => $centerId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('projects', 'cost_center_id')) {
            Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('cost_center_id'));
        }
        if (Schema::hasColumn('businesses', 'industry')) {
            Schema::table('businesses', fn (Blueprint $table) => $table->dropColumn('industry'));
        }
        Schema::dropIfExists('accounting_audit_logs');
        Schema::dropIfExists('accounting_allocations');
        Schema::dropIfExists('accounting_budgets');
        Schema::dropIfExists('cost_centers');
        Schema::dropIfExists('departments');
    }
};
