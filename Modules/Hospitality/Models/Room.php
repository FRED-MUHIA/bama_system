<?php

namespace Modules\Hospitality\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends HospitalityModel
{
    protected $table = 'hospitality_rooms';

    protected $fillable = ['tenant_id', 'business_id', 'room_type_id', 'room_number', 'status', 'capacity', 'floor', 'view', 'bed_type', 'amenities', 'price_per_night', 'sort_order', 'notes'];

    protected $casts = ['amenities' => 'array', 'price_per_night' => 'decimal:2'];

    public const STATUSES = ['Available', 'Occupied', 'Reserved', 'Cleaning', 'Maintenance', 'Out Of Service'];

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function housekeepingTasks(): HasMany
    {
        return $this->hasMany(HousekeepingTask::class);
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }
}
