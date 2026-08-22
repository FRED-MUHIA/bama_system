<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HandoverChecklistItem extends Model
{
    protected $fillable = ['handover_record_id', 'label', 'is_done', 'notes'];
    protected $casts = ['is_done' => 'boolean'];

    public function handoverRecord() { return $this->belongsTo(HandoverRecord::class); }
}
