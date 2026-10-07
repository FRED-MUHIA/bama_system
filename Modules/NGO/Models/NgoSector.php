<?php

namespace Modules\NGO\Models;

class NgoSector extends NgoModel
{
    protected $table = 'ngo_sectors';
    protected $casts = ['is_active' => 'boolean'];
}
