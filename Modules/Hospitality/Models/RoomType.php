<?php

namespace Modules\Hospitality\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends HospitalityModel
{
    protected $table = 'hospitality_room_types';

    protected $fillable = ['tenant_id', 'business_id', 'name', 'slug', 'description', 'capacity', 'base_price', 'amenities', 'is_active'];

    protected $casts = ['amenities' => 'array', 'is_active' => 'boolean', 'base_price' => 'decimal:2'];

    public const DEFAULTS = ['Standard', 'Deluxe', 'Executive', 'Suite', 'Presidential Suite'];

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
