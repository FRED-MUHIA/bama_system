<?php

namespace Modules\Hospitality\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRequest extends HospitalityModel
{
    protected $table = 'hospitality_maintenance_requests';

    protected $fillable = ['tenant_id', 'business_id', 'room_id', 'category', 'priority', 'status', 'title', 'description', 'assigned_to', 'resolved_at', 'closed_at'];

    protected $casts = ['resolved_at' => 'datetime', 'closed_at' => 'datetime'];

    public const CATEGORIES = ['Electrical', 'Plumbing', 'Furniture', 'Internet', 'Air Conditioning', 'General'];
    public const PRIORITIES = ['Low', 'Medium', 'High', 'Critical'];
    public const STATUSES = ['Open', 'Assigned', 'In Progress', 'Resolved', 'Closed'];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
