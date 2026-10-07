<?php

namespace Modules\NGO\Models;

class NgoDonor extends NgoModel
{
    protected $table = 'ngo_donors';
    public function programs() { return $this->hasMany(NgoProgram::class, 'donor_id'); }
}
