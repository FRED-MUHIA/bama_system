<?php

namespace Modules\Hospitality\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HousekeepingTask extends HospitalityModel
{
    protected $table = 'hospitality_housekeeping_tasks';

    protected $fillable = ['tenant_id', 'business_id', 'room_id', 'task_type', 'status', 'assigned_to', 'assigned_at', 'started_at', 'completed_at', 'completion_minutes', 'notes'];

    protected $casts = ['assigned_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime'];

    public const TYPES = ['Room Cleaning', 'Deep Cleaning', 'Laundry', 'Inspection'];
    public const STATUSES = ['Pending', 'Assigned', 'In Progress', 'Completed'];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
