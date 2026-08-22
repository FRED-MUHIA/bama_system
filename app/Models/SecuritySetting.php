<?php
namespace App\Models;use App\Models\Concerns\BelongsToBusiness;use Illuminate\Database\Eloquent\Model;class SecuritySetting extends Model{use BelongsToBusiness;protected $fillable=['business_id','max_failed_attempts','lockout_minutes','session_timeout_minutes','invitation_expiry_hours','password_expiry_days','password_history_count'];}
