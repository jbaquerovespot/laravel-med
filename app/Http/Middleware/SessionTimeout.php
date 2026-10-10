<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $timeout = config('hce.session_timeout', 900); // 15 minutes default
        $lastActivity = Session::get('last_activity', now()->timestamp);
        $currentTime = now()->timestamp;

        // Check if session has expired
        if (($currentTime - $lastActivity) > $timeout) {
            $this->logSessionExpired();

            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();

            return redirect()->route('login')
                ->with('session_expired', true);
        }

        // Update last activity timestamp
        Session::put('last_activity', $currentTime);

        return $next($request);
    }

    /**
     * Log session expiration event for audit.
     */
    protected function logSessionExpired(): void
    {
        if (! config('hce.audit_enabled', true)) {
            return;
        }

        $user = Auth::user();

        if ($user) {
            activity()
                ->causedBy($user)
                ->withProperties([
                    'event' => 'user.session_expired',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'timestamp' => now()->toIso8601String(),
                ])
                ->log('Sesión expirada por inactividad');
        }
    }
}
