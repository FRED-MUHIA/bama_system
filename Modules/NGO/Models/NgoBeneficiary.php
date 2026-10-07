<?php

namespace Modules\NGO\Models;

class NgoBeneficiary extends NgoModel
{
    protected $table = 'ngo_beneficiaries';
    protected $casts = ['date_of_birth' => 'date', 'registered_on' => 'date', 'household_information' => 'array', 'vulnerability_information' => 'array', 'custom_fields' => 'array'];
    public function programs() { return $this->belongsToMany(NgoProgram::class, 'ngo_program_beneficiaries', 'beneficiary_id', 'program_id')->withPivot(['project_id', 'enrolled_on', 'exited_on', 'status', 'custom_fields'])->withTimestamps(); }
}
