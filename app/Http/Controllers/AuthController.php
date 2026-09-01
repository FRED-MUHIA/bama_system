<?php

namespace App\Http\Controllers;

use App\Models\LoginToken;
use App\Models\OtpCode;
use App\Models\SecuritySetting;
use App\Models\User;
use App\Services\IamService;
use App\Services\OutgoingMailService;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login', [
            'loginContext' => $this->loginContext(),
            'loginSystem' => $this->loginSystemSnapshot(),
            'otpAvailable' => $this->schemaHasTable('otp_codes'),
        ]);
    }

    public function login(Request $request)
    {
        return $this->attemptLogin($request, $this->loginContext());
    }

    public function ownerLogin(Request $request)
    {
        return $this->attemptLogin($request, 'owner');
    }

    public function portalLogin(Request $request)
    {
        return $this->attemptLogin($request, 'portal');
    }

    private function attemptLogin(Request $request, string $context)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);
        $throttleKey = $this->ensureNotRateLimited($request, 'password-login', $context.'|'.$data['username'], 5, 60, 'username');

        $loginField = filter_var($data['username'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($loginField, $data['username'])->first();
        if ($user?->locked_at && $user->locked_at->isFuture()) {
            return back()->withErrors(['username' => 'Account temporarily locked. Try again later or contact an administrator.']);
        }
        if ($user && ! in_array($user->status ?? 'Active', ['Active'])) {
            return back()->withErrors(['username' => 'This account is '.$user->status.'.']);
        }
        if ($user && Schema::hasColumn('users', 'enable_password_login') && ! $user->enable_password_login) {
            return back()->withErrors(['username' => 'Password login is disabled for this user. Use OTP or magic link.'])->onlyInput('username');
        }

        if (Auth::attempt([
            $loginField => $data['username'],
            'password' => $data['password'],
            'is_active' => true,
        ], $request->boolean('remember'))) {
            $request->session()->regenerate();
            $authenticated = Auth::user();
            if (! $this->accountMatchesContext($authenticated, $context) || ! $this->establishLoginContext($authenticated, $context)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors(['username' => $this->invalidContextMessage($context)])->onlyInput('username');
            }

            $loginUpdates = collect([
                'failed_login_attempts' => 0,
                'locked_at' => null,
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->filter(fn ($value, $column) => Schema::hasColumn('users', $column))->all();
            if ($loginUpdates) {
                $authenticated->update($loginUpdates);
            }
            if (Schema::hasTable('login_activities')) {
                app(IamService::class)->recordLogin($request, $authenticated, true);
            }
            RateLimiter::clear($throttleKey);

            return redirect()->intended($this->landingRouteFor($authenticated));
        }

        RateLimiter::hit($throttleKey, 60);

        if ($user && Schema::hasColumn('users', 'failed_login_attempts')) {
            $attempts = ($user->failed_login_attempts ?? 0) + 1;
            $securitySettings = Schema::hasTable('security_settings') ? SecuritySetting::first() : null;
            $max = $securitySettings?->max_failed_attempts ?? 5;
            $lockoutMinutes = $securitySettings?->lockout_minutes ?? 30;
            $failedUpdates = ['failed_login_attempts' => $attempts];
            if (Schema::hasColumn('users', 'locked_at')) {
                $failedUpdates['locked_at'] = $attempts >= $max ? now()->addMinutes($lockoutMinutes) : null;
            }
            $user->update($failedUpdates);
        }
        if (Schema::hasTable('login_activities')) {
            app(IamService::class)->recordLogin($request, $user, false);
        }

        return back()->withErrors(['username' => 'Invalid login details or inactive account.'])->onlyInput('username');
    }

    public function requestOtp(Request $request)
    {
        abort_unless(Schema::hasTable('otp_codes'), 404);

        $context = $this->loginContext($request);
        $data = $request->validate(['email' => ['required', 'email']]);
        $throttleKey = $this->ensureNotRateLimited($request, 'otp-request', $context.'|'.$data['email'], 1, 60, 'email');
        RateLimiter::hit($throttleKey, 60);
        $user = User::where('email', $data['email'])->where('is_active', true)->first();
        if (! $user || ! $this->accountMatchesContext($user, $context) || (Schema::hasColumn('users', 'enable_otp_login') && ! $user->enable_otp_login)) {
            return back()->withErrors(['email' => 'OTP login is not available for this account.']);
        }

        $otp = OtpCode::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code' => (string) random_int(100000, 999999),
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            app(OutgoingMailService::class)->sendRaw(
                $user->email,
                'BAMA login OTP for '.$this->profileLabelFor($user, $context),
                $this->otpEmailBody($user, $otp->code, $context),
                businessId: $this->businessIdFor($user, $context),
            );
        } catch (\Throwable $e) {
            $otp->delete();
            report($e);

            return back()->withErrors(['email' => 'The OTP email could not be delivered. Please contact an administrator.']);
        }

        return back()->with(['status' => 'OTP sent to '.$user->email.'. Check your inbox and spam folder.', 'otp_sent' => true, 'otp_email' => $user->email, 'otp_context' => $context, 'otp_resend_at' => now()->addSeconds(60)->timestamp]);
    }

    public function verifyOtp(Request $request)
    {
        abort_unless(Schema::hasTable('otp_codes'), 404);

        $context = $this->loginContext($request);
        $data = $request->validate(['email' => ['required', 'email'], 'code' => ['required', 'digits:6']]);
        $throttleKey = $this->ensureNotRateLimited($request, 'otp-verify', $context.'|'.$data['email'], 5, 300, 'code');
        $otp = OtpCode::where('email', $data['email'])->where('code', $data['code'])->whereNull('used_at')->where('expires_at', '>', now())->latest()->first();
        if (! $otp || ! $otp->user || ! $this->accountMatchesContext($otp->user, $context)) {
            RateLimiter::hit($throttleKey, 300);

            return back()->withErrors(['code' => 'Invalid or expired OTP.']);
        }

        $otp->update(['used_at' => now()]);
        Auth::login($otp->user);
        $request->session()->regenerate();
        if (! $this->establishLoginContext($otp->user, $context)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route($this->loginRouteFor($context))->withErrors(['username' => $this->invalidContextMessage($context)]);
        }
        RateLimiter::clear($throttleKey);

        return redirect()->intended($this->landingRouteFor($otp->user));
    }

    public function requestMagicLink(Request $request)
    {
        abort_unless(Schema::hasTable('login_tokens'), 404);

        $context = $this->loginContext($request);
        $data = $request->validate(['email' => ['required', 'email']]);
        $throttleKey = $this->ensureNotRateLimited($request, 'magic-link', $context.'|'.$data['email'], 3, 300, 'email');
        $user = User::where('email', $data['email'])->where('is_active', true)->first();
        if (! $user || ! $this->accountMatchesContext($user, $context) || (Schema::hasColumn('users', 'enable_magic_link_login') && ! $user->enable_magic_link_login)) {
            RateLimiter::hit($throttleKey, 300);

            return back()->withErrors(['email' => 'Magic link login is not available for this account.']);
        }

        $token = LoginToken::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'token' => Str::random(64),
            'expires_at' => now()->addMinutes(15),
        ]);

        try {
            app(OutgoingMailService::class)->sendRaw(
                $user->email,
                'BAMA magic login link for '.$this->profileLabelFor($user, $context),
                $this->magicLinkEmailBody($user, route('login.magic.consume', ['token' => $token->token, 'context' => $context]), $context),
                businessId: $this->businessIdFor($user, $context),
            );
        } catch (\Throwable $e) {
            $token->delete();
            report($e);

            return back()->withErrors(['email' => 'The magic-link email could not be delivered. Please contact an administrator.']);
        }

        RateLimiter::hit($throttleKey, 300);

        return back()->with('status', 'Magic link sent to '.$user->email.'.');
    }

    public function consumeMagicLink(Request $request, string $token)
    {
        abort_unless(Schema::hasTable('login_tokens'), 404);

        $context = $this->loginContext($request);
        $loginToken = LoginToken::where('token', $token)->whereNull('used_at')->where('expires_at', '>', now())->firstOrFail();
        abort_unless($this->accountMatchesContext($loginToken->user, $context), 404);
        $loginToken->update(['used_at' => now()]);
        Auth::login($loginToken->user);
        $request->session()->regenerate();
        if (! $this->establishLoginContext($loginToken->user, $context)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route($this->loginRouteFor($context))->withErrors(['username' => $this->invalidContextMessage($context)]);
        }

        return redirect()->intended($this->landingRouteFor($loginToken->user));
    }

    public function logout(Request $request)
    {
        if (Schema::hasTable('login_activities')) {
            app(IamService::class)->recordLogin($request, $request->user(), true, 'logout');
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out securely.');
    }

    public function forgotForm()
    {
        return view('auth.forgot-password');
    }

    public function forgot(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        $throttleKey = $this->ensureNotRateLimited($request, 'password-reset', $request->input('email'), 5, 300, 'email');
        RateLimiter::hit($throttleKey, 300);

        try {
            $this->applyMailSettings();
            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['email' => 'The password-reset email could not be delivered. Please contact an administrator.']);
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function resetForm(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function ($user, $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    private function applyMailSettings(): void
    {
        app(OutgoingMailService::class)->apply();
    }

    private function ensureNotRateLimited(Request $request, string $scope, string $identity, int $maxAttempts, int $decaySeconds, string $field): string
    {
        $key = $this->rateLimitKey($request, $scope, $identity);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                $field => "Too many attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        return $key;
    }

    private function rateLimitKey(Request $request, string $scope, string $identity): string
    {
        return $scope.':'.sha1(Str::lower($identity).'|'.$request->ip());
    }

    private function accountMatchesContext(?User $user, string $context): bool
    {
        return match ($context) {
            'owner' => $user?->role === 'super_admin',
            'portal' => $user?->role === 'client_portal',
            default => $user && ! in_array($user->role, ['super_admin', 'client_portal'], true),
        };
    }

    private function establishLoginContext(User $user, string $context): bool
    {
        ActiveBusiness::clear();

        if ($context === 'owner' || $context === 'portal') {
            ActiveTenant::clear();

            if (Schema::hasColumn('users', 'current_tenant_id') && $user->current_tenant_id) {
                $user->forceFill(['current_tenant_id' => null])->saveQuietly();
            }

            return true;
        }

        $tenant = ActiveTenant::firstTenantFor($user);
        if (! $tenant) {
            return false;
        }

        ActiveTenant::switchTo($tenant);

        return (bool) ActiveBusiness::current();
    }

    private function landingRouteFor(User $user): string
    {
        return match ($user->role) {
            'super_admin' => route('platform.dashboard'),
            'client_portal' => route('portal.dashboard'),
            default => route('dashboard'),
        };
    }

    private function otpEmailBody(User $user, string $code, string $context): string
    {
        return implode("\n", [
            'Hello '.$user->name.',',
            '',
            'Use this one-time password to sign in to BAMA.',
            'Workspace/profile: '.$this->profileLabelFor($user, $context),
            'Account email: '.$user->email,
            'Login area: '.$this->contextLabel($context),
            '',
            'OTP: '.$code,
            'Expires in: 10 minutes',
            '',
            'If you did not request this login, do not share this code with anyone.',
            '',
            'BAMA secure workspace access',
        ]);
    }

    private function magicLinkEmailBody(User $user, string $url, string $context): string
    {
        return implode("\n", [
            'Hello '.$user->name.',',
            '',
            'Use this secure link to sign in to BAMA.',
            'Workspace/profile: '.$this->profileLabelFor($user, $context),
            'Account email: '.$user->email,
            'Login area: '.$this->contextLabel($context),
            '',
            'Login link:',
            $url,
            '',
            'This link expires in 15 minutes and can be used once.',
            'If you did not request this login, do not click the link.',
            '',
            'BAMA secure workspace access',
        ]);
    }

    private function profileLabelFor(User $user, string $context): string
    {
        if ($context === 'owner') {
            return 'BAMA owner console';
        }

        if ($context === 'portal') {
            return 'BAMA client portal';
        }

        if ($businessId = $this->businessIdFor($user, $context)) {
            $businessName = DB::table('businesses')->where('id', $businessId)->value('name');
            if ($businessName) {
                return $businessName;
            }
        }

        if ($user->currentTenant?->name) {
            return $user->currentTenant->name;
        }

        return config('app.name', 'BAMA');
    }

    private function businessIdFor(User $user, string $context): ?int
    {
        if ($context !== 'business' || ! Schema::hasTable('business_user') || ! Schema::hasTable('businesses')) {
            return null;
        }

        if (ActiveBusiness::id()) {
            return ActiveBusiness::id();
        }

        $businessId = DB::table('business_user')
            ->where('user_id', $user->id)
            ->whereIn('status', ['Active', 'Pending Invitation'])
            ->orderBy('business_id')
            ->value('business_id');

        return $businessId ? (int) $businessId : null;
    }

    private function contextLabel(string $context): string
    {
        return match ($context) {
            'owner' => 'Owner management',
            'portal' => 'Client portal',
            default => 'Business workspace',
        };
    }

    private function loginContext(?Request $request = null): string
    {
        $request ??= request();

        if ($request->routeIs('platform.*') || $request->routeIs('public.platform.*')) {
            return 'owner';
        }

        if ($request->routeIs('portal.login*') || $request->routeIs('public.portal.login*')) {
            return 'portal';
        }

        if ($request->routeIs('login.magic.consume')) {
            $context = $request->query('context');

            return in_array($context, ['owner', 'portal', 'business'], true) ? $context : 'business';
        }

        if ($request->routeIs('login') || $request->routeIs('login.*')) {
            return 'business';
        }

        $context = $request->input('login_context') ?: $request->query('context');

        return in_array($context, ['owner', 'portal', 'business'], true) ? $context : 'business';
    }

    private function loginRouteFor(string $context): string
    {
        return match ($context) {
            'owner' => 'platform.login',
            'portal' => 'portal.login',
            default => 'login',
        };
    }

    private function loginSystemSnapshot(): array
    {
        $fallbackIndustries = count(config('industry-packages.industries', []));

        $snapshot = [
            'workspaces' => 'Ready',
            'modules' => $fallbackIndustries ?: 'Live',
            'industries' => $fallbackIndustries ?: 'Many',
            'security' => 'Encrypted',
        ];

        try {
            if (Schema::hasTable('tenants')) {
                $snapshot['workspaces'] = max(1, (int) DB::table('tenants')->count());
            } elseif (Schema::hasTable('businesses')) {
                $snapshot['workspaces'] = max(1, (int) DB::table('businesses')->count());
            }

            if (Schema::hasTable('modules')) {
                $snapshot['modules'] = max(1, (int) DB::table('modules')->where('is_active', true)->count());
                $industryCount = DB::table('modules')
                    ->whereNotNull('industry')
                    ->where('industry', '!=', '')
                    ->distinct()
                    ->pluck('industry')
                    ->filter()
                    ->count();

                if ($industryCount > 0) {
                    $snapshot['industries'] = $industryCount;
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $snapshot;
    }

    private function schemaHasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    private function invalidContextMessage(string $context): string
    {
        return match ($context) {
            'owner' => 'Use a platform owner account on this login page.',
            'portal' => 'Use a client portal account on this login page.',
            default => 'Use a business workspace account on this login page.',
        };
    }
}
