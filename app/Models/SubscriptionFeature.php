<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionFeature extends Model
{
    protected $fillable = ['plan_id', 'feature', 'value', 'limit', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];

    public function plan() { return $this->belongsTo(Plan::class); }
}
