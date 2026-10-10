<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.auth')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    #[Locked]
    public bool $isLocked = false;

    #[Locked]
    public int $lockoutSeconds = 0;

    protected int $maxAttempts = 5;

    protected int $lockoutMinutes = 30;

    public function mount(): void
    {
        $this->maxAttempts = (int) config('hce.max_login_attempts', 5);
        $this->lockoutMinutes = (int) config('hce.lockout_duration', 30);

        $this->checkLockout();
    }

    /**
     * Get the rate limiting throttle key for the component.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    /**
     * Check if the account is currently locked out.
     */
    protected function checkLockout(): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey(), $this->maxAttempts)) {
            $this->isLocked = true;
            $this->lockoutSeconds = RateLimiter::availableIn($this->throttleKey());
        }
    }

    /**
     * Attempt to authenticate the user.
     */
    public function login(): void
    {
        $this->validate();

        // Check lockout status
        if (RateLimiter::tooManyAttempts($this->throttleKey(), $this->maxAttempts)) {
            $this->isLocked = true;
            $this->lockoutSeconds = RateLimiter::availableIn($this->throttleKey());
            $seconds = $this->lockoutSeconds;
            $minutes = ceil($seconds / 60);

            $this->addError('email', __('Cuenta bloqueada. Intente nuevamente en :minutes minutos.', [
                'minutes' => $minutes,
            ]));

            $this->logFailedAttempt('account_locked');

            return;
        }

        // Attempt authentication
        if (Auth::attempt([
            'email' => $this->email,
            'password' => $this->password,
        ], $this->remember)) {
            // Clear rate limiter on success
            RateLimiter::clear($this->throttleKey());

            // Regenerate session
            Session::regenerate();

            // Update last activity timestamp
            Session::put('last_activity', now()->timestamp);

            // Log successful login
            $this->logSuccessfulLogin();

            // Redirect to intended page or dashboard
            $this->redirectIntended(route('dashboard'), navigate: true);

            return;
        }

        // Increment failed attempts
        RateLimiter::hit($this->throttleKey(), $this->lockoutMinutes * 60);

        // Check if this was the last attempt
        $remaining = $this->maxAttempts - RateLimiter::attempts($this->throttleKey());

        if ($remaining <= 0) {
            $this->isLocked = true;
            $this->lockoutSeconds = $this->lockoutMinutes * 60;
            $this->addError('email', __('Cuenta bloqueada. Intente nuevamente en :minutes minutos.', [
                'minutes' => $this->lockoutMinutes,
            ]));
        } else {
            $this->addError('email', __('Las credenciales no son válidas.'));
        }

        $this->logFailedAttempt('invalid_credentials');

        // Reset password field for security
        $this->password = '';
    }

    /**
     * Log successful login event.
     */
    protected function logSuccessfulLogin(): void
    {
        if (! config('hce.audit_enabled', true)) {
            return;
        }

        activity()
            ->causedBy(Auth::user())
            ->withProperties([
                'event' => 'user.login',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toIso8601String(),
            ])
            ->log('Usuario inició sesión');
    }

    /**
     * Log failed login attempt.
     */
    protected function logFailedAttempt(string $reason): void
    {
        if (! config('hce.audit_enabled', true)) {
            return;
        }

        activity()
            ->withProperties([
                'event' => 'user.login_failed',
                'email' => $this->email,
                'reason' => $reason,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toIso8601String(),
            ])
            ->log('Intento de inicio de sesión fallido');
    }

    /**
     * Refresh lockout timer (called via polling).
     */
    public function refreshLockout(): void
    {
        if ($this->isLocked) {
            $this->lockoutSeconds = max(0, RateLimiter::availableIn($this->throttleKey()));

            if ($this->lockoutSeconds <= 0) {
                $this->isLocked = false;
                RateLimiter::clear($this->throttleKey());
            }
        }
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
