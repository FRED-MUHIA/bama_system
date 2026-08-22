<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class WarrantyClaim extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'warranty_id', 'claim_date', 'status', 'issue', 'resolution'];
    protected $casts = ['claim_date' => 'date'];

    public function warranty() { return $this->belongsTo(Warranty::class); }
}
