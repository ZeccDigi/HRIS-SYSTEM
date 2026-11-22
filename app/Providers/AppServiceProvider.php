<?php

namespace App\Providers;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Validation\Rules\Password::defaults(fn () => \Illuminate\Validation\Rules\Password::min(8)->rules([new \App\Rules\AcceptablePasswordStrength()]));
        View::composer(['layouts.app', 'layouts.sidebar', 'dashboard'], function () {
            if (Auth::check()) {
                Auth::user()->loadMissing('employee');
            }
        });

        Event::listen(Login::class, [\App\Listeners\AuditLogListener::class, 'handleLogin']);
        Event::listen(Failed::class, [\App\Listeners\AuditLogListener::class, 'handleFailed']);
        Event::listen(Logout::class, [\App\Listeners\AuditLogListener::class, 'handleLogout']);
    }
}
