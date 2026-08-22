<?php

namespace Modules\Hospitality\Models;

class RestaurantTable extends HospitalityModel
{
    protected $table = 'hospitality_restaurant_tables';

    protected $fillable = ['tenant_id', 'business_id', 'table_number', 'section', 'capacity', 'status', 'notes'];

    public const STATUSES = ['Available', 'Reserved', 'Occupied', 'Cleaning', 'Out Of Service'];
}
