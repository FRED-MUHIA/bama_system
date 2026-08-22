<?php

namespace Modules\Hospitality\Models;

class Unit extends HospitalityModel
{
    protected $table = 'hospitality_units';

    protected $fillable = ['tenant_id', 'business_id', 'name', 'symbol', 'type'];

    public const TYPES = ['Weight', 'Volume', 'Quantity', 'Package'];
}
