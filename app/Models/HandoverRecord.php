<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class HandoverRecord extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'project_id', 'handover_date', 'status', 'signature_name', 'signature_data', 'notes'];
    protected $casts = ['handover_date' => 'date'];

    public function project() { return $this->belongsTo(Project::class); }
    public function checklistItems() { return $this->hasMany(HandoverChecklistItem::class); }
}
