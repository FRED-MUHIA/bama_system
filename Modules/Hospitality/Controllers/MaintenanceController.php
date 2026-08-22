<?php

namespace Modules\Hospitality\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Hospitality\Models\MaintenanceRequest;
use Modules\Hospitality\Models\Room;

class MaintenanceController extends Controller
{
    public function index()
    {
        return view('hospitality.index', [
            'title' => 'Maintenance',
            'section' => 'maintenance',
            'records' => MaintenanceRequest::with('room', 'assignedUser')->latest()->paginate(30),
            'rooms' => Room::orderBy('room_number')->get(),
            'users' => User::orderBy('name')->get(),
            'categories' => MaintenanceRequest::CATEGORIES,
            'priorities' => MaintenanceRequest::PRIORITIES,
            'statuses' => MaintenanceRequest::STATUSES,
            'completionStats' => [
                'completed' => MaintenanceRequest::whereIn('status', ['Resolved', 'Closed'])->count(),
                'open' => MaintenanceRequest::whereNotIn('status', ['Resolved', 'Closed'])->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => ['nullable', 'exists:hospitality_rooms,id'],
            'category' => ['required', Rule::in(MaintenanceRequest::CATEGORIES)],
            'priority' => ['required', Rule::in(MaintenanceRequest::PRIORITIES)],
            'status' => ['required', Rule::in(MaintenanceRequest::STATUSES)],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        MaintenanceRequest::create($data);

        return back()->with('status', 'Maintenance request created.');
    }

    public function update(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $data = $request->validate(['status' => ['required', Rule::in(MaintenanceRequest::STATUSES)]]);
        $dates = match ($data['status']) {
            'Resolved' => ['resolved_at' => now()],
            'Closed' => ['closed_at' => now()],
            default => [],
        };

        $maintenanceRequest->update($data + $dates);

        return back()->with('status', 'Maintenance request updated.');
    }
}
