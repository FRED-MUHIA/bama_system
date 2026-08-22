<?php

namespace App\Http\Controllers;

use App\Models\LoginToken;
use App\Models\OtpCode;
use App\Models\SecuritySetting;
use App\Models\User;
use App\Services\IamService;
use App\Services\OutgoingMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

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
            $loginUpdates = collect([
                'failed_login_attempts' => 0,
                'locked_at' => null,
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->filter(fn ($value, $column) => Schema::hasColumn('users', $column))->all();
            if ($loginUpdates) {
                $user->update($loginUpdates);
            }
            if (Schema::hasTable('login_activities')) {
                app(IamService::class)->recordLogin($request, $user, true);
            }

            return redirect()->intended(Auth::user()->role === 'client_portal' ? route('portal.dashboard') : route('dashboard'));
        }

        if ($user && Schema::hasColumn('users', 'failed_login_attempts')) {
            $attempts = ($user->failed_login_attempts ?? 0) + 1;
            $max = Schema::hasTable('security_settings') ? (SecuritySetting::first()?->max_failed_attempts ?? 5) : 5;
            $failedUpdates = ['failed_login_attempts' => $attempts];
            if (Schema::hasColumn('users', 'locked_at')) {
                $failedUpdates['locked_at'] = $attempts >= $max ? now()->addMinutes(30) : null;
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

        $data = $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', $data['email'])->where('is_active', true)->first();
        if (! $user || (Schema::hasColumn('users', 'enable_otp_login') && ! $user->enable_otp_login)) {
            return back()->withErrors(['email' => 'OTP login is not available for this account.']);
        }

        $resendKey = 'login-otp:'.sha1(strtolower($data['email']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($resendKey, 1)) {
            $seconds = RateLimiter::availableIn($resendKey);

            return back()->with(['otp_sent' => true, 'otp_email' => $data['email'], 'otp_resend_at' => now()->addSeconds($seconds)->timestamp])
                ->withErrors(['email' => "Please wait {$seconds} seconds before requesting another OTP."]);
        }

        $otp = OtpCode::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code' => (string) random_int(100000, 999999),
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            app(OutgoingMailService::class)->sendRaw($user->email, 'BAMA login OTP', 'Your BAMA login OTP is '.$otp->code);
        } catch (\Throwable $e) {
            $otp->delete();
            report($e);

            return back()->withErrors(['email' => 'The OTP email could not be delivered. Please contact an administrator.']);
        }

        RateLimiter::hit($resendKey, 60);

        return back()->with(['status' => 'OTP sent to '.$user->email.'. Check your inbox and spam folder.', 'otp_sent' => true, 'otp_email' => $user->email, 'otp_resend_at' => now()->addSeconds(60)->timestamp]);
    }

    public function verifyOtp(Request $request)
    {
        abort_unless(Schema::hasTable('otp_codes'), 404);

        $data = $request->validate(['email' => ['required', 'email'], 'code' => ['required', 'digits:6']]);
        $otp = OtpCode::where('email', $data['email'])->where('code', $data['code'])->whereNull('used_at')->where('expires_at', '>', now())->latest()->first();
        if (! $otp || ! $otp->user) {
            return back()->withErrors(['code' => 'Invalid or expired OTP.']);
        }

        $otp->update(['used_at' => now()]);
        Auth::login($otp->user);
        $request->session()->regenerate();

        return redirect()->intended($otp->user->role === 'client_portal' ? route('portal.dashboard') : route('dashboard'));
    }

    public function requestMagicLink(Request $request)
    {
        abort_unless(Schema::hasTable('login_tokens'), 404);

        $data = $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', $data['email'])->where('is_active', true)->first();
        if (! $user || (Schema::hasColumn('users', 'enable_magic_link_login') && ! $user->enable_magic_link_login)) {
            return back()->withErrors(['email' => 'Magic link login is not available for this account.']);
        }

        $token = LoginToken::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'token' => Str::random(64),
            'expires_at' => now()->addMinutes(15),
        ]);

        try {
            app(OutgoingMailService::class)->sendRaw($user->email, 'BAMA magic login link', 'Login here: '.route('login.magic.consume', $token->token));
        } catch (\Throwable $e) {
            $token->delete();
            report($e);

            return back()->withErrors(['email' => 'The magic-link email could not be delivered. Please contact an administrator.']);
        }

        return back()->with('status', 'Magic link sent to '.$user->email.'.');
    }

    public function consumeMagicLink(Request $request, string $token)
    {
        abort_unless(Schema::hasTable('login_tokens'), 404);

        $loginToken = LoginToken::where('token', $token)->whereNull('used_at')->where('expires_at', '>', now())->firstOrFail();
        $loginToken->update(['used_at' => now()]);
        Auth::login($loginToken->user);
        $request->session()->regenerate();

        return redirect()->intended($loginToken->user->role === 'client_portal' ? route('portal.dashboard') : route('dashboard'));
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
}
