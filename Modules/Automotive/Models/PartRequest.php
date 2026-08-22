<?php

namespace Modules\Automotive\Models;

use App\Models\User;

class PartRequest extends AutomotiveModel
{
    protected $table = 'automotive_part_requests';

    public function jobCard() { return $this->belongsTo(JobCard::class); }
    public function requestedBy() { return $this->belongsTo(User::class, 'requested_by'); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
    public function items() { return $this->hasMany(PartRequestItem::class); }
}
