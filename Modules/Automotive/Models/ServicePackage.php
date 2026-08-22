<?php

namespace Modules\Automotive\Models;

class ServicePackage extends AutomotiveModel
{
    protected $table = 'automotive_service_packages';

    protected $casts = ['labour' => 'array', 'parts' => 'array', 'fluids' => 'array', 'is_active' => 'boolean'];
}
