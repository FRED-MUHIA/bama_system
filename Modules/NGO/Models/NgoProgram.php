<?php

namespace Modules\NGO\Models;

class NgoProgram extends NgoModel
{
    protected $table = 'ngo_programs';
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'budget' => 'decimal:2'];

    public function sector() { return $this->belongsTo(NgoSector::class, 'ngo_sector_id'); }
    public function donor() { return $this->belongsTo(NgoDonor::class, 'donor_id'); }
    public function activities() { return $this->hasMany(NgoActivity::class, 'program_id'); }
    public function beneficiaries() { return $this->belongsToMany(NgoBeneficiary::class, 'ngo_program_beneficiaries', 'program_id', 'beneficiary_id')->withPivot(['project_id', 'enrolled_on', 'exited_on', 'status', 'custom_fields'])->withTimestamps(); }
}
