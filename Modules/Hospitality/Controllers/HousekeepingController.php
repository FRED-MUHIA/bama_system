<?php

namespace Modules\Hospitality\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Hospitality\Models\HousekeepingTask;
use Modules\Hospitality\Models\Room;

class HousekeepingController extends Controller
{
    public function index()
    {
        return view('hospitality.index', [
            'title' => 'Housekeeping',
            'section' => 'housekeeping',
            'records' => HousekeepingTask::with('room', 'assignedUser')->latest()->paginate(30),
            'rooms' => Room::orderBy('room_number')->get(),
            'users' => User::orderBy('name')->get(),
            'types' => HousekeepingTask::TYPES,
            'statuses' => HousekeepingTask::STATUSES,
            'completionStats' => [
                'completed' => HousekeepingTask::where('status', 'Completed')->count(),
                'open' => HousekeepingTask::where('status', '!=', 'Completed')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => ['nullable', 'exists:hospitality_rooms,id'],
            'task_type' => ['required', Rule::in(HousekeepingTask::TYPES)],
            'status' => ['required', Rule::in(HousekeepingTask::STATUSES)],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        HousekeepingTask::create($data + ['assigned_at' => ! empty($data['assigned_to']) ? now() : null]);

        return back()->with('status', 'Housekeeping task created.');
    }

    public function update(Request $request, HousekeepingTask $task)
    {
        $data = $request->validate(['status' => ['required', Rule::in(HousekeepingTask::STATUSES)]]);
        $timestamps = match ($data['status']) {
            'In Progress' => ['started_at' => now()],
            'Completed' => ['completed_at' => now(), 'completion_minutes' => $task->started_at ? $task->started_at->diffInMinutes(now()) : null],
            default => [],
        };

        $task->update($data + $timestamps);

        return back()->with('status', 'Housekeeping task updated.');
    }
}
