<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TenantDashboardWidget extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'user_id', 'dashboard_widget_id', 'sort_order', 'width', 'enabled', 'settings'];

    protected $casts = ['enabled' => 'boolean', 'settings' => 'array'];

    public function widget() { return $this->belongsTo(DashboardWidget::class, 'dashboard_widget_id'); }
}
