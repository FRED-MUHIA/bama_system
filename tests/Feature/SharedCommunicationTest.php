<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Client;
use App\Models\Department;
use App\Models\IamRole;
use App\Models\Project;
use App\Models\User;
use App\Services\IamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Shared\Communication\Contracts\CommunicationServiceContract;
use Shared\Communication\Models\CommunicationPermission;
use Shared\Communication\Models\CommunicationNotification;
use Shared\Communication\Models\Message;
use Tests\TestCase;

class SharedCommunicationTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::where('slug', 'bama')->firstOrFail();
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'status' => 'Active']);
        $this->actingAs($this->admin)->withSession(['active_business_id' => $this->business->id]);
        app(IamService::class)->bootstrap();
    }

    public function test_scoped_channel_messages_mentions_attachments_and_notifications_are_recorded(): void
    {
        $department = Department::create(['name' => 'Finance Department', 'code' => 'FIN']);
        $branch = Branch::create(['name' => 'Nairobi Branch', 'code' => 'NRB', 'is_active' => true]);
        $recipient = User::factory()->create(['name' => 'Jane Manager', 'username' => 'Jane', 'role' => 'staff', 'is_active' => true]);
        $this->assignBusinessUser($recipient, departmentId: $department->id, branchId: $branch->id);

        $communication = app(CommunicationServiceContract::class);
        $channel = $communication->createChannel([
            'name' => 'Finance Department',
            'type' => 'Department',
            'department_id' => $department->id,
            'branch_id' => $branch->id,
            'member_ids' => [$recipient->id],
        ], $this->admin);

        $message = $communication->sendMessage($channel, $this->admin, [
            'body' => '@Jane please review the branch cash report with @everyone.',
            'attachments' => [[
                'file_name' => 'cash-report.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 2400,
                'path' => 'documents/cash-report.pdf',
            ]],
        ]);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame('Delivered', $message->status);
        $this->assertDatabaseHas('message_attachments', ['message_id' => $message->id, 'file_name' => 'cash-report.pdf']);
        $this->assertDatabaseHas('mentions', ['message_id' => $message->id, 'mentioned_type' => 'User', 'mentioned_id' => $recipient->id]);
        $this->assertDatabaseHas('mentions', ['message_id' => $message->id, 'mentioned_type' => 'Everyone']);
        $this->assertDatabaseHas('notifications', ['user_id' => $recipient->id, 'notification_type' => 'Mention']);
        $this->assertDatabaseHas('communication_audit_logs', ['event' => 'message.sent']);

        $communication->markRead($channel, $recipient, $message);
        $this->assertDatabaseHas('channel_members', ['communication_channel_id' => $channel->id, 'user_id' => $recipient->id, 'last_read_message_id' => $message->id]);
    }

    public function test_communication_matrix_can_block_role_to_role_direct_messages(): void
    {
        $cashier = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $director = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $cashierRole = IamRole::where('business_id', $this->business->id)->where('slug', 'cashier')->firstOrFail();
        $directorRole = IamRole::where('business_id', $this->business->id)->where('slug', 'retail-director')->firstOrFail();

        $this->assignBusinessUser($cashier, $cashierRole->id);
        $this->assignBusinessUser($director, $directorRole->id);

        CommunicationPermission::create([
            'role_slug' => 'cashier',
            'target_type' => 'Role',
            'target_role_slug' => 'retail-director',
            'can_message' => false,
        ]);

        $this->expectException(ValidationException::class);

        app(CommunicationServiceContract::class)->directChannel($cashier, $director);
    }

    public function test_communication_api_exposes_channels_messages_announcements_and_notifications(): void
    {
        $recipient = User::factory()->create(['name' => 'API Recipient', 'is_active' => true]);
        $systemRole = IamRole::where('business_id', $this->business->id)->where('slug', 'system-administrator')->firstOrFail();
        $this->assignBusinessUser($recipient, $systemRole->id);

        $channelResponse = $this->postJson(route('api.v1.communication.channels.store'), [
            'name' => 'Operations Channel',
            'type' => 'Group',
            'member_ids' => [$recipient->id],
        ])->assertCreated();

        $channelId = $channelResponse->json('data.id');

        $this->postJson(route('api.v1.communication.messages.store'), [
            'channel_id' => $channelId,
            'body' => 'Operations update for @everyone',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'Delivered');

        $this->getJson(route('api.v1.communication.messages.index', ['channel_id' => $channelId]))
            ->assertOk()
            ->assertJsonPath('data.0.body', 'Operations update for @everyone');

        $this->postJson(route('api.v1.communication.announcements.store'), [
            'scope_type' => 'Company',
            'title' => 'Critical maintenance',
            'body' => 'System maintenance tonight.',
            'priority' => 'Critical',
        ])->assertCreated()
            ->assertJsonPath('data.priority', 'Critical');

        $this->getJson(route('api.v1.communication.announcements.index'))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Critical maintenance']);

        $this->postJson(route('api.v1.communication.notifications.store'), [
            'user_id' => $recipient->id,
            'notification_type' => 'Task',
            'title' => 'Review transfer',
            'body' => 'Please review inventory transfer.',
        ])->assertCreated();

        $this->actingAs($recipient)->withSession(['active_business_id' => $this->business->id]);
        $this->getJson(route('api.v1.communication.notifications.index'))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Review transfer']);
    }

    public function test_dashboard_header_shows_message_alert_dropdown(): void
    {
        CommunicationNotification::create([
            'user_id' => $this->admin->id,
            'notification_type' => 'Message',
            'delivery_channel' => 'In-App',
            'title' => 'New manager message',
            'body' => 'Please review the daily close notes.',
            'status' => 'Unread',
            'action_url' => route('communication.center'),
            'delivered_at' => now(),
        ]);

        $this->get(route('administration.index'))
            ->assertOk()
            ->assertSee('Hello')
            ->assertSee('Messages &amp; Alerts', false)
            ->assertSee('New manager message')
            ->assertSee('Messaging')
            ->assertSee('Tax &amp; ETIMS', false);
    }

    public function test_message_actions_read_react_save_pin_edit_and_delete_work_through_api(): void
    {
        $recipient = User::factory()->create(['name' => 'Action User', 'role' => 'staff', 'is_active' => true]);
        $systemRole = IamRole::where('business_id', $this->business->id)->where('slug', 'system-administrator')->firstOrFail();
        $this->assignBusinessUser($recipient, $systemRole->id);

        $communication = app(CommunicationServiceContract::class);
        $channel = $communication->createChannel([
            'name' => 'Action Channel',
            'type' => 'Group',
            'member_ids' => [$recipient->id],
        ], $this->admin);
        $message = $communication->sendMessage($channel, $this->admin, ['body' => 'Please review the pinned update.']);

        $this->actingAs($recipient)->withSession(['active_business_id' => $this->business->id]);

        $this->postJson(route('api.v1.communication.channels.read', $channel), ['message_id' => $message->id])->assertOk();
        $this->postJson(route('api.v1.communication.messages.reactions.store', $message), ['reaction' => '+1'])->assertCreated();
        $this->postJson(route('api.v1.communication.messages.save', $message))->assertCreated();
        $this->postJson(route('api.v1.communication.messages.pin', $message), ['note' => 'Keep visible'])->assertCreated();
        $this->putJson(route('api.v1.communication.messages.update', $message), ['body' => 'Please review the updated pinned note.'])->assertOk();
        $this->deleteJson(route('api.v1.communication.messages.destroy', $message))->assertOk();

        $this->assertDatabaseHas('message_reads', ['message_id' => $message->id, 'user_id' => $recipient->id]);
        $this->assertDatabaseHas('message_reactions', ['message_id' => $message->id, 'user_id' => $recipient->id, 'reaction' => '+1']);
        $this->assertDatabaseHas('saved_messages', ['message_id' => $message->id, 'user_id' => $recipient->id]);
        $this->assertDatabaseHas('conversation_pins', ['message_id' => $message->id, 'pinned_by' => $recipient->id]);
        $this->assertDatabaseHas('message_deletions', ['message_id' => $message->id, 'user_id' => $recipient->id]);
        $this->assertDatabaseHas('messages', ['id' => $message->id, 'body' => 'Please review the updated pinned note.']);
    }

    public function test_directory_search_and_direct_messages_do_not_cross_business_boundaries(): void
    {
        $ownUser = User::factory()->create(['name' => 'Same Business Employee', 'role' => 'staff', 'is_active' => true]);
        $foreignUser = User::factory()->create(['name' => 'Foreign Business Employee', 'role' => 'staff', 'is_active' => true]);
        $otherBusiness = Business::create(['name' => 'Other Business', 'slug' => 'other-business', 'is_active' => true]);

        $this->assignBusinessUser($ownUser);
        DB::table('business_user')->updateOrInsert(
            ['business_id' => $otherBusiness->id, 'user_id' => $foreignUser->id],
            ['status' => 'Active', 'created_at' => now(), 'updated_at' => now()]
        );

        $this->getJson(route('api.v1.communication.directory.index'))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Same Business Employee'])
            ->assertJsonMissing(['name' => 'Foreign Business Employee']);

        $this->getJson(route('api.v1.communication.search', ['q' => 'Foreign']))
            ->assertOk()
            ->assertJsonMissing(['name' => 'Foreign Business Employee']);

        $this->expectException(ValidationException::class);
        app(CommunicationServiceContract::class)->directChannel($this->admin, $foreignUser);
    }

    public function test_branch_announcements_are_only_visible_to_their_audience_and_can_be_acknowledged(): void
    {
        $nairobi = Branch::create(['name' => 'Nairobi', 'code' => 'NRB', 'is_active' => true]);
        $mombasa = Branch::create(['name' => 'Mombasa', 'code' => 'MBA', 'is_active' => true]);
        $nairobiUser = User::factory()->create(['name' => 'Nairobi Staff', 'role' => 'staff', 'is_active' => true]);
        $mombasaUser = User::factory()->create(['name' => 'Mombasa Staff', 'role' => 'staff', 'is_active' => true]);
        $systemRole = IamRole::where('business_id', $this->business->id)->where('slug', 'system-administrator')->firstOrFail();

        $this->assignBusinessUser($nairobiUser, $systemRole->id, branchId: $nairobi->id);
        $this->assignBusinessUser($mombasaUser, $systemRole->id, branchId: $mombasa->id);

        $announcement = app(CommunicationServiceContract::class)->publishAnnouncement([
            'scope_type' => 'Branch',
            'branch_id' => $nairobi->id,
            'title' => 'Nairobi branch standup',
            'body' => 'Branch standup at 9.',
            'priority' => 'High',
            'requires_acknowledgement' => true,
        ], $this->admin);

        $this->actingAs($mombasaUser)->withSession(['active_business_id' => $this->business->id]);
        $this->getJson(route('api.v1.communication.announcements.index'))
            ->assertOk()
            ->assertJsonMissing(['title' => 'Nairobi branch standup']);

        $this->actingAs($nairobiUser)->withSession(['active_business_id' => $this->business->id]);
        $this->getJson(route('api.v1.communication.announcements.index'))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Nairobi branch standup']);
        $this->postJson(route('api.v1.communication.announcements.acknowledge', $announcement), ['acknowledge' => true])->assertOk();

        $this->assertDatabaseHas('announcement_acknowledgements', ['announcement_id' => $announcement->id, 'user_id' => $nairobiUser->id]);
    }

    public function test_contextual_record_channel_can_be_created_for_core_erp_records(): void
    {
        $client = Client::create(['type' => 'Business', 'name' => 'Context Client', 'email' => 'context@example.test']);
        $project = Project::create(['client_id' => $client->id, 'project_name' => 'Context Project', 'status' => 'Lead']);

        $response = $this->postJson(route('api.v1.communication.context.store'), [
            'record_type' => 'project',
            'record_id' => $project->id,
            'name' => 'Project War Room',
        ])->assertCreated();

        $response->assertJsonPath('data.type', 'Record')
            ->assertJsonPath('data.name', 'Project War Room');

        $this->assertDatabaseHas('communication_channels', [
            'id' => $response->json('data.id'),
            'record_type' => Project::class,
            'record_id' => $project->id,
        ]);
    }

    private function assignBusinessUser(User $user, ?int $roleId = null, ?int $departmentId = null, ?int $branchId = null): void
    {
        DB::table('business_user')->updateOrInsert(
            ['business_id' => $this->business->id, 'user_id' => $user->id],
            [
                'iam_role_id' => $roleId,
                'department_id' => $departmentId,
                'branch_id' => $branchId,
                'status' => 'Active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
