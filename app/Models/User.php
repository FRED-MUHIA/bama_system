<?php

namespace App\Models;

use App\Notifications\WorkspaceVerifyEmailNotification;
use App\Notifications\BamaResetPasswordNotification;
use App\Services\IamService;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'current_tenant_id',
        'username',
        'email',
        'role',
        'is_active',
        'enable_password_login',
        'enable_otp_login',
        'enable_magic_link_login',
        'password',
        'employee_number', 'job_title', 'phone', 'status', 'manager_id', 'photo_path', 'signature_path', 'preferred_language', 'timezone', 'date_joined', 'notes', 'failed_login_attempts', 'locked_at', 'last_login_at', 'last_login_ip', 'presence_status', 'presence_custom_status', 'last_seen_at', 'force_password_change', 'password_changed_at', 'session_version', 'dashboard_layout', 'notification_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'enable_password_login' => 'boolean',
            'enable_otp_login' => 'boolean',
            'enable_magic_link_login' => 'boolean',
            'password' => 'hashed',
            'date_joined' => 'date', 'locked_at' => 'datetime', 'last_login_at' => 'datetime', 'last_seen_at' => 'datetime', 'password_changed_at' => 'datetime', 'force_password_change' => 'boolean', 'dashboard_layout' => 'array', 'notification_preferences' => 'array',
        ];
    }

    public function otpCodes()
    {
        return $this->hasMany(OtpCode::class);
    }

    public function loginTokens()
    {
        return $this->hasMany(LoginToken::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class);
    }

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class)->withPivot(['role', 'status', 'joined_at'])->withTimestamps();
    }

    public function currentTenant()
    {
        return $this->belongsTo(Tenant::class, 'current_tenant_id');
    }

    public function hasPermission(string $permission): bool
    {
        return app(IamService::class)->can($this, $permission);
    }

    public function directPermissions()
    {
        return $this->belongsToMany(IamPermission::class, 'iam_permission_user');
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new WorkspaceVerifyEmailNotification);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token)
    {
        $this->notify(new BamaResetPasswordNotification($token));
    }
}
