<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['slug', 'name', 'monthly_price', 'currency', 'limits', 'is_active'];

    protected $casts = ['limits' => 'array', 'is_active' => 'boolean'];

    public function features() { return $this->hasMany(SubscriptionFeature::class); }
}
