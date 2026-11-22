<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        // Check against the User's role (which defaults to 'user' via Employee relation)
        // Convert both to uppercase for comparison
        $userRole = strtoupper($request->user()->role);
        $allowedRoles = array_map('strtoupper', $roles);

        if (! in_array($userRole, $allowedRoles)) {
            abort(403, 'Unauthorized access. Your role is: '.$userRole);
        }

        return $next($request);
    }
}
