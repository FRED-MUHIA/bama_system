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
        if (Schema::hasTable('communication_channels')) {
            Schema::table('communication_channels', function (Blueprint $table) {
                if (! Schema::hasColumn('communication_channels', 'description')) {
                    $table->text('description')->nullable()->after('name');
                }
                if (! Schema::hasColumn('communication_channels', 'avatar_path')) {
                    $table->string('avatar_path')->nullable()->after('description');
                }
                if (! Schema::hasColumn('communication_channels', 'archived_at')) {
                    $table->timestamp('archived_at')->nullable()->after('last_message_at');
                }
            });
        }

        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                if (! Schema::hasColumn('messages', 'edited_by')) {
                    $table->foreignId('edited_by')->nullable()->after('edited_at')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('messages', 'deleted_by')) {
                    $table->foreignId('deleted_by')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('messages', 'moderation_reason')) {
                    $table->text('moderation_reason')->nullable()->after('deleted_by');
                }
            });
        }

        if (Schema::hasTable('message_attachments')) {
            Schema::table('message_attachments', function (Blueprint $table) {
                if (! Schema::hasColumn('message_attachments', 'disk')) {
                    $table->string('disk')->default('local')->after('document_media_id');
                }
                if (! Schema::hasColumn('message_attachments', 'is_voice_note')) {
                    $table->boolean('is_voice_note')->default(false)->after('path');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'presence_status')) {
                    $table->string('presence_status')->default('Offline')->after('last_login_ip');
                }
                if (! Schema::hasColumn('users', 'presence_custom_status')) {
                    $table->string('presence_custom_status')->nullable()->after('presence_status');
                }
                if (! Schema::hasColumn('users', 'last_seen_at')) {
                    $table->timestamp('last_seen_at')->nullable()->after('presence_custom_status');
                }
            });
        }

        if (! Schema::hasTable('communication_settings')) {
            Schema::create('communication_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->boolean('chat_enabled')->default(true);
                $table->boolean('allow_direct_messages')->default(true);
                $table->boolean('allow_employee_group_creation')->default(false);
                $table->boolean('allow_file_sharing')->default(true);
                $table->unsignedInteger('max_attachment_size_kb')->default(10240);
                $table->boolean('allow_message_editing')->default(true);
                $table->unsignedInteger('message_edit_time_limit_minutes')->default(15);
                $table->boolean('allow_message_deletion')->default(true);
                $table->boolean('enable_read_receipts')->default(true);
                $table->boolean('enable_presence')->default(true);
                $table->boolean('enable_typing_indicators')->default(true);
                $table->boolean('allow_everyone_mentions')->default(false);
                $table->boolean('auto_department_channels')->default(true);
                $table->boolean('auto_team_channels')->default(true);
                $table->boolean('auto_branch_channels')->default(true);
                $table->unsignedInteger('message_retention_days')->nullable();
                $table->json('industry_channel_templates')->nullable();
                $table->timestamps();
                $table->unique('business_id');
            });
        }

        if (! Schema::hasTable('message_reads')) {
            Schema::create('message_reads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('communication_channel_id')->constrained('communication_channels')->cascadeOnDelete();
                $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamp('read_at');
                $table->timestamps();
                $table->unique(['message_id', 'user_id']);
                $table->index(['business_id', 'communication_channel_id', 'user_id'], 'message_reads_lookup');
            });
        }

        if (! Schema::hasTable('saved_messages')) {
            Schema::create('saved_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['message_id', 'user_id']);
                $table->index(['business_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('conversation_pins')) {
            Schema::create('conversation_pins', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('communication_channel_id')->constrained('communication_channels')->cascadeOnDelete();
                $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
                $table->foreignId('pinned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('note')->nullable();
                $table->timestamps();
                $table->unique(['communication_channel_id', 'message_id'], 'conversation_pin_unique');
            });
        }

        if (! Schema::hasTable('message_deletions')) {
            Schema::create('message_deletions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamp('deleted_at');
                $table->timestamps();
                $table->unique(['message_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('announcement_acknowledgements')) {
            Schema::create('announcement_acknowledgements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('acknowledged_at')->nullable();
                $table->timestamps();
                $table->unique(['announcement_id', 'user_id'], 'announcement_ack_unique');
                $table->index(['business_id', 'user_id']);
            });
        }

        $this->addCommunicationIndexes();
        $this->registerCommunicationPermissions();
    }

    public function down(): void
    {
        foreach ([
            'announcement_acknowledgements',
            'message_deletions',
            'conversation_pins',
            'saved_messages',
            'message_reads',
            'communication_settings',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                foreach (['last_seen_at', 'presence_custom_status', 'presence_status'] as $column) {
                    if (Schema::hasColumn('users', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function addCommunicationIndexes(): void
    {
        if (Schema::hasTable('messages') && ! Schema::hasIndex('messages', 'messages_business_channel_created_idx')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->index(['business_id', 'communication_channel_id', 'created_at'], 'messages_business_channel_created_idx');
            });
        }

        if (Schema::hasTable('message_attachments') && ! Schema::hasIndex('message_attachments', 'attachments_business_message_idx')) {
            Schema::table('message_attachments', function (Blueprint $table) {
                $table->index(['business_id', 'message_id'], 'attachments_business_message_idx');
            });
        }

        if (Schema::hasTable('announcements') && ! Schema::hasIndex('announcements', 'announcements_business_audience_idx')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->index(['business_id', 'status', 'scope_type', 'branch_id', 'department_id'], 'announcements_business_audience_idx');
            });
        }

        if (Schema::hasTable('communication_channels') && ! Schema::hasIndex('communication_channels', 'channels_business_scope_idx')) {
            Schema::table('communication_channels', function (Blueprint $table) {
                $table->index(['business_id', 'type', 'department_id', 'branch_id', 'team_id'], 'channels_business_scope_idx');
            });
        }
    }

    private function registerCommunicationPermissions(): void
    {
        $permissions = [
            'communication.view',
            'communication.send',
            'communication.create_group',
            'communication.manage_group',
            'communication.create_channel',
            'communication.manage_channel',
            'communication.upload',
            'communication.delete_own',
            'communication.moderate',
            'communication.announcements.create',
            'communication.announcements.manage',
            'communication.mass_mention',
            'communication.audit',
            'communication.settings',
            'communication.manage',
            'communication.admin',
            'communication.announce',
            'communication.reports',
        ];

        if (Schema::hasTable('iam_permissions')) {
            foreach ($permissions as $permission) {
                DB::table('iam_permissions')->updateOrInsert(
                    ['name' => $permission],
                    [
                        'module' => 'communication',
                        'description' => Str::headline(str_replace(['communication.', '.', '-'], ['Communication ', ' ', ' '], $permission)),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        if (Schema::hasTable('modules')) {
            DB::table('modules')->where('slug', 'shared-communication')->update([
                'permissions' => json_encode($permissions),
                'menu' => json_encode([
                    'label' => 'Messages',
                    'group' => 'Shared',
                    'icon' => 'bi-chat-dots',
                    'route' => 'communication.center',
                ]),
                'updated_at' => now(),
            ]);
        }
    }
};
