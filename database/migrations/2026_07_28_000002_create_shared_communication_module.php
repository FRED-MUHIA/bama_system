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
        if (! Schema::hasTable('communication_channels')) {
            Schema::create('communication_channels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
                $table->string('industry')->nullable()->index();
                $table->string('role_slug')->nullable()->index();
                $table->nullableMorphs('record');
                $table->string('name');
                $table->string('slug');
                $table->string('type')->default('Group');
                $table->string('visibility')->default('Private');
                $table->boolean('is_private')->default(true);
                $table->json('settings')->nullable();
                $table->dateTime('last_message_at')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'slug']);
                $table->index(['business_id', 'type']);
            });
        }

        if (! Schema::hasTable('channel_members')) {
            Schema::create('channel_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('communication_channel_id')->constrained('communication_channels')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('member_role')->default('Member');
                $table->string('status')->default('Active');
                $table->boolean('muted')->default(false);
                $table->unsignedBigInteger('last_read_message_id')->nullable();
                $table->dateTime('last_read_at')->nullable();
                $table->timestamps();
                $table->unique(['communication_channel_id', 'user_id'], 'channel_member_unique');
                $table->index(['business_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('communication_channel_id')->constrained('communication_channels')->cascadeOnDelete();
                $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('messages')->nullOnDelete();
                $table->nullableMorphs('related');
                $table->string('message_type')->default('Message');
                $table->longText('body');
                $table->string('status')->default('Sent');
                $table->dateTime('delivered_at')->nullable();
                $table->dateTime('edited_at')->nullable();
                $table->dateTime('deleted_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'communication_channel_id']);
                $table->index(['business_id', 'message_type']);
            });
        }

        if (! Schema::hasTable('message_reactions')) {
            Schema::create('message_reactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('reaction', 80);
                $table->timestamps();
                $table->unique(['message_id', 'user_id', 'reaction']);
            });
        }

        if (! Schema::hasTable('message_attachments')) {
            Schema::create('message_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedBigInteger('document_media_id')->nullable();
                $table->string('file_name');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('path')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('communication_channel_id')->nullable()->constrained('communication_channels')->nullOnDelete();
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('scope_type')->default('Company');
                $table->unsignedBigInteger('scope_id')->nullable();
                $table->string('industry')->nullable();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title');
                $table->longText('body');
                $table->string('priority')->default('Medium');
                $table->dateTime('publish_at')->nullable();
                $table->dateTime('expires_at')->nullable();
                $table->boolean('requires_acknowledgement')->default(false);
                $table->json('read_by')->nullable();
                $table->json('acknowledged_by')->nullable();
                $table->string('status')->default('Draft');
                $table->timestamps();
                $table->index(['business_id', 'scope_type', 'priority']);
            });
        }

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->nullableMorphs('notifiable');
                $table->string('notification_type')->default('Message');
                $table->string('delivery_channel')->default('In-App');
                $table->string('title');
                $table->text('body')->nullable();
                $table->string('status')->default('Unread');
                $table->string('action_url')->nullable();
                $table->json('payload')->nullable();
                $table->dateTime('delivered_at')->nullable();
                $table->dateTime('read_at')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'user_id', 'status']);
            });
        }

        if (! Schema::hasTable('notification_preferences')) {
            Schema::create('notification_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('notification_type');
                $table->boolean('in_app')->default(true);
                $table->boolean('push')->default(false);
                $table->boolean('email')->default(false);
                $table->boolean('sms')->default(false);
                $table->timestamps();
                $table->unique(['business_id', 'user_id', 'notification_type'], 'notification_pref_unique');
            });
        }

        if (! Schema::hasTable('mentions')) {
            Schema::create('mentions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('message_id')->nullable()->constrained('messages')->cascadeOnDelete();
                $table->foreignId('announcement_id')->nullable()->constrained('announcements')->cascadeOnDelete();
                $table->string('mentioned_type');
                $table->unsignedBigInteger('mentioned_id')->nullable();
                $table->string('token');
                $table->dateTime('notified_at')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'mentioned_type']);
            });
        }

        if (! Schema::hasTable('communication_permissions')) {
            Schema::create('communication_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('role_slug')->nullable();
                $table->string('target_type')->default('Role');
                $table->unsignedBigInteger('target_id')->nullable();
                $table->string('target_role_slug')->nullable();
                $table->boolean('can_message')->default(true);
                $table->boolean('can_create_channels')->default(false);
                $table->boolean('can_announce')->default(false);
                $table->json('rules')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'role_slug', 'target_type'], 'communication_permission_lookup');
            });
        }

        if (! Schema::hasTable('communication_audit_logs')) {
            Schema::create('communication_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('event');
                $table->nullableMorphs('auditable');
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'event']);
            });
        }

        $this->registerSharedCommunicationModule();
    }

    public function down(): void
    {
        foreach ([
            'communication_audit_logs',
            'communication_permissions',
            'mentions',
            'notification_preferences',
            'notifications',
            'announcements',
            'message_attachments',
            'message_reactions',
            'messages',
            'channel_members',
            'communication_channels',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function registerSharedCommunicationModule(): void
    {
        $now = now();
        $permissions = ['communication.view', 'communication.manage', 'communication.admin', 'communication.announce', 'communication.reports'];

        if (Schema::hasTable('iam_permissions')) {
            foreach ($permissions as $permission) {
                DB::table('iam_permissions')->updateOrInsert(
                    ['name' => $permission],
                    [
                        'module' => 'communication',
                        'description' => Str::headline(str_replace(['communication.', '.', '-'], ['Communication ', ' ', ' '], $permission)),
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }

        if (Schema::hasTable('modules')) {
            DB::table('modules')->updateOrInsert(
                ['slug' => 'shared-communication'],
                [
                    'name' => 'Shared Communication',
                    'namespace' => 'Shared\\Communication',
                    'type' => 'shared',
                    'industry' => null,
                    'icon' => 'bi-chat-dots',
                    'route' => 'communication.center',
                    'permissions' => json_encode($permissions),
                    'menu' => json_encode(['label' => 'Communication', 'group' => 'Shared', 'icon' => 'bi-chat-dots', 'route' => 'communication.center']),
                    'widgets' => json_encode(['communication-unread-messages', 'communication-recent-conversations', 'communication-announcements', 'communication-pending-mentions', 'communication-team-activity']),
                    'is_core' => true,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        if (Schema::hasTable('dashboard_widgets')) {
            foreach ([
                ['communication-unread-messages', 'Unread Messages'],
                ['communication-recent-conversations', 'Recent Conversations'],
                ['communication-department-activity', 'Department Activity'],
                ['communication-announcements', 'Announcements'],
                ['communication-pending-mentions', 'Pending Mentions'],
                ['communication-team-activity', 'Team Activity'],
            ] as [$slug, $name]) {
                DB::table('dashboard_widgets')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'module_slug' => 'shared-communication',
                        'industry' => null,
                        'component' => 'communication.widgets.metric-card',
                        'permission' => 'communication.view',
                        'settings_schema' => json_encode(['supports_scope_filters' => true, 'supports_period_filters' => true]),
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }
};
