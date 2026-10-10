<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Livewire\Component;

class Logout extends Component
{
    /**
     * Log the user out of the application.
     */
    public function logout(): void
    {
        $this->logLogout();

        Auth::logout();

        Session::invalidate();
        Session::regenerateToken();

        $this->redirect(route('login'), navigate: true);
    }

    /**
     * Log logout event for audit.
     */
    protected function logLogout(): void
    {
        if (! config('hce.audit_enabled', true)) {
            return;
        }

        $user = Auth::user();

        if ($user) {
            activity()
                ->causedBy($user)
                ->withProperties([
                    'event' => 'user.logout',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'timestamp' => now()->toIso8601String(),
                ])
                ->log('Usuario cerró sesión');
        }
    }

    public function render(): View
    {
        return view('livewire.auth.logout');
    }
}
