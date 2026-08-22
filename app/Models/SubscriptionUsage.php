<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SubscriptionUsage extends Model
{
    use BelongsToTenant;

    protected $table = 'subscription_usage';

    protected $fillable = ['tenant_id', 'feature', 'used', 'resets_at'];

    protected $casts = ['resets_at' => 'datetime'];
}
