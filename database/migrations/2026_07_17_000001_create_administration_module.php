<?php

use App\Support\DatabasePlatform;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = Schema::getColumnListing('users');

            if (! in_array('employee_number', $columns, true)) {
                $table->string('employee_number')->nullable()->unique();
            }
            if (! in_array('job_title', $columns, true)) {
                $table->string('job_title')->nullable();
            }
            if (! in_array('phone', $columns, true)) {
                $table->string('phone')->nullable();
            }
            if (! in_array('status', $columns, true)) {
                $table->string('status')->default('Active');
            }
            if (! in_array('manager_id', $columns, true)) {
                $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! in_array('photo_path', $columns, true)) {
                $table->string('photo_path')->nullable();
            }
            if (! in_array('signature_path', $columns, true)) {
                $table->string('signature_path')->nullable();
            }
            if (! in_array('preferred_language', $columns, true)) {
                $table->string('preferred_language', 10)->default('en');
            }
            if (! in_array('timezone', $columns, true)) {
                $table->string('timezone')->default('Africa/Nairobi');
            }
            if (! in_array('date_joined', $columns, true)) {
                $table->date('date_joined')->nullable();
            }
            if (! in_array('notes', $columns, true)) {
                $table->text('notes')->nullable();
            }
            if (! in_array('failed_login_attempts', $columns, true)) {
                $table->unsignedSmallInteger('failed_login_attempts')->default(0);
            }
            if (! in_array('locked_at', $columns, true)) {
                $table->timestamp('locked_at')->nullable();
            }
            if (! in_array('last_login_at', $columns, true)) {
                $table->timestamp('last_login_at')->nullable();
            }
            if (! in_array('last_login_ip', $columns, true)) {
                $table->string('last_login_ip', 45)->nullable();
            }
            if (! in_array('force_password_change', $columns, true)) {
                $table->boolean('force_password_change')->default(false);
            }
            if (! in_array('password_changed_at', $columns, true)) {
                $table->timestamp('password_changed_at')->nullable();
            }
            if (! in_array('session_version', $columns, true)) {
                $table->unsignedInteger('session_version')->default(1);
            }
        });

        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code');
                $table->text('address')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['business_id', 'code']);
            });
        }

        if (! Schema::hasTable('iam_roles')) {
            Schema::create('iam_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->text('description')->nullable();
                $table->string('landing_route')->default('dashboard');
                $table->boolean('is_system')->default(false);
                $table->timestamps();
                $table->unique(['business_id', 'slug']);
            });
        }

        if (! Schema::hasTable('iam_permissions')) {
            Schema::create('iam_permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('module');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('iam_permission_role')) {
            Schema::create('iam_permission_role', function (Blueprint $table) {
                $table->foreignId('iam_role_id')->constrained('iam_roles')->cascadeOnDelete();
                $table->foreignId('iam_permission_id')->constrained('iam_permissions')->cascadeOnDelete();
                $table->primary(['iam_role_id', 'iam_permission_id']);
            });
        }

        if (! Schema::hasTable('business_user')) {
            Schema::create('business_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('iam_role_id')->nullable()->constrained('iam_roles')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->string('status')->default('Active');
                $table->timestamps();
                $table->unique(['business_id', 'user_id']);
            });
        } else {
            Schema::table('business_user', function (Blueprint $table) {
                $columns = Schema::getColumnListing('business_user');

                if (! in_array('iam_role_id', $columns, true)) {
                    $table->foreignId('iam_role_id')->nullable()->after('role')->constrained('iam_roles')->nullOnDelete();
                }
                if (! in_array('department_id', $columns, true)) {
                    $table->foreignId('department_id')->nullable()->after('iam_role_id')->constrained()->nullOnDelete();
                }
                if (! in_array('branch_id', $columns, true)) {
                    $table->foreignId('branch_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
                }
                if (! in_array('status', $columns, true)) {
                    $table->string('status')->default('Active')->after('branch_id');
                }
            });

            DatabasePlatform::deleteDuplicates('business_user', ['business_id', 'user_id']);

            if (! $this->indexExists('business_user', 'business_user_business_id_user_id_unique')) {
                Schema::table('business_user', function (Blueprint $table) {
                    $table->unique(['business_id', 'user_id']);
                });
            }
        }

        if (! Schema::hasTable('teams')) {
            Schema::create('teams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('type')->default('Project');
                $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('team_user')) {
            Schema::create('team_user', function (Blueprint $table) {
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->primary(['team_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('user_invitations')) {
            Schema::create('user_invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('token', 64)->unique();
                $table->timestamp('expires_at');
                $table->timestamp('accepted_at')->nullable();
                $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('login_activities')) {
            Schema::create('login_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('email')->nullable();
                $table->string('event');
                $table->boolean('successful')->default(false);
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('device')->nullable();
                $table->string('browser')->nullable();
                $table->string('operating_system')->nullable();
                $table->string('location')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('user_devices')) {
            Schema::create('user_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('fingerprint');
                $table->string('name')->nullable();
                $table->text('user_agent')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->boolean('is_trusted')->default(false);
                $table->timestamp('last_activity_at');
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'fingerprint']);
            });
        }

        if (! Schema::hasTable('approval_workflows')) {
            Schema::create('approval_workflows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('document_type');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('approval_workflow_steps')) {
            Schema::create('approval_workflow_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('approval_workflow_id')->constrained()->cascadeOnDelete();
                $table->foreignId('iam_role_id')->nullable()->constrained('iam_roles')->nullOnDelete();
                $table->unsignedSmallInteger('step_order');
                $table->string('name');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('approval_requests')) {
            Schema::create('approval_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('approval_workflow_id')->constrained()->cascadeOnDelete();
                $table->string('approvable_type');
                $table->unsignedBigInteger('approvable_id');
                $table->unsignedSmallInteger('current_step')->default(1);
                $table->string('status')->default('Pending');
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('approval_actions')) {
            Schema::create('approval_actions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('approval_request_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action');
                $table->text('comments')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('admin_audit_logs')) {
            Schema::create('admin_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('event');
                $table->string('subject_type')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('password_histories')) {
            Schema::create('password_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('password');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('security_settings')) {
            Schema::create('security_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete()->unique();
                $table->unsignedSmallInteger('max_failed_attempts')->default(5);
                $table->unsignedSmallInteger('lockout_minutes')->default(30);
                $table->unsignedSmallInteger('session_timeout_minutes')->default(120);
                $table->unsignedSmallInteger('invitation_expiry_hours')->default(24);
                $table->unsignedSmallInteger('password_expiry_days')->nullable();
                $table->unsignedSmallInteger('password_history_count')->default(5);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'security_settings',
            'password_histories',
            'admin_audit_logs',
            'approval_actions',
            'approval_requests',
            'approval_workflow_steps',
            'approval_workflows',
            'user_devices',
            'login_activities',
            'user_invitations',
            'team_user',
            'teams',
            'iam_permission_role',
            'iam_permissions',
            'iam_roles',
            'branches',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return DatabasePlatform::hasIndex($table, $index, 'unique');
    }
};
