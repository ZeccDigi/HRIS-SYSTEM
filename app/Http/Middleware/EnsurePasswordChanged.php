<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $allowedRoutes = ['profile.edit', 'password.otp.send', 'password.update', 'logout'];

        if ($routeName && in_array($routeName, $allowedRoutes, true)) {
            return $next($request);
        }

        if ($request->is('livewire/*')) {
            return $next($request);
        }

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Please change your temporary password before continuing.');
    }
}
