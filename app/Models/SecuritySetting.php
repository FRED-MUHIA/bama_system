<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class SecuritySetting extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'max_failed_attempts', 'lockout_minutes', 'session_timeout_minutes', 'invitation_expiry_hours', 'password_expiry_days', 'password_history_count', 'pos_void_pin', 'pos_edit_pin'];

    protected $hidden = ['pos_void_pin', 'pos_edit_pin'];

    protected function posVoidPin(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => filled($value) ? Hash::make($value) : null);
    }

    protected function posEditPin(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => filled($value) ? Hash::make($value) : null);
    }

    public function verifiesPosVoidPin(string $pin): bool
    {
        return filled($this->getRawOriginal('pos_void_pin')) && Hash::check($pin, $this->getRawOriginal('pos_void_pin'));
    }

    public function verifiesPosEditPin(string $pin): bool
    {
        return filled($this->getRawOriginal('pos_edit_pin')) && Hash::check($pin, $this->getRawOriginal('pos_edit_pin'));
    }
}
