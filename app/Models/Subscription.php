<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'plan_id', 'status', 'starts_at', 'trial_ends_at', 'renews_at', 'ends_at', 'metadata'];

    protected $casts = ['starts_at' => 'datetime', 'trial_ends_at' => 'datetime', 'renews_at' => 'datetime', 'ends_at' => 'datetime', 'metadata' => 'array'];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function plan() { return $this->belongsTo(Plan::class); }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing'], true) && (! $this->ends_at || $this->ends_at->isFuture());
    }
}
