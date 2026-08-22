<?php

namespace Modules\Automotive\Models;

class LabourOperation extends AutomotiveModel
{
    protected $table = 'automotive_labour_operations';

    protected $casts = ['is_active' => 'boolean'];
}
