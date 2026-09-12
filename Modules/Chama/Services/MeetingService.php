<?php

namespace Modules\Chama\Services;

use Illuminate\Support\Facades\DB;
use Modules\Chama\Models\Attendance;
use Modules\Chama\Models\Meeting;
use Modules\Chama\Models\Member;

class MeetingService
{
    public function __construct(private ChamaNumberService $numbers, private ChamaAuditService $audit) {}

    public function create(array $data): Meeting
    {
        return DB::transaction(function () use ($data) {
            $meeting = Meeting::create(array_merge($data, [
                'meeting_number' => $data['meeting_number'] ?? $this->numbers->meetingNumber(),
                'status' => $data['status'] ?? 'Scheduled',
            ]));

            $this->audit->record('meeting.created', $meeting);

            return $meeting;
        });
    }

    public function markAttendance(Meeting $meeting, Member $member, string $status, float $fineAmount = 0): Attendance
    {
        return DB::transaction(function () use ($meeting, $member, $status, $fineAmount) {
            $attendance = Attendance::updateOrCreate(
                ['meeting_id' => $meeting->id, 'member_id' => $member->id],
                [
                    'status' => $status,
                    'checked_in_at' => in_array($status, ['Present', 'Late'], true) ? now() : null,
                    'fine_amount' => $fineAmount,
                ]
            );

            $this->refreshQuorum($meeting);
            $this->audit->record('meeting.attendance.marked', $attendance);

            return $attendance;
        });
    }

    public function refreshQuorum(Meeting $meeting): Meeting
    {
        $present = $meeting->attendance()->whereIn('status', ['Present', 'Late'])->count();
        $activeMembers = max(Member::where('status', 'Active')->count(), 1);
        $required = $meeting->quorum_required_count
            ?: (int) ceil($activeMembers * ((float) $meeting->quorum_required_percent ?: 50) / 100);

        $meeting->update([
            'members_present_count' => $present,
            'quorum_required_count' => $required,
            'quorum_achieved' => $present >= $required,
        ]);

        return $meeting->refresh();
    }
}
