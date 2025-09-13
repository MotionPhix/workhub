<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAfterLogin
{
    /**
     * Handle an incoming request.
     *
     * This middleware redirects authenticated users to their appropriate dashboard
     * based on their role and prevents them from accessing login/register pages.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $user = $request->user();

            // Determine the appropriate redirect route based on user role
            if ($user->can('access-admin-panel')) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->hasRole('manager')) {
                return redirect()->route('dashboard')->with('info', 'Welcome back, Manager!');
            } else {
                return redirect()->route('dashboard')->with('info', 'Welcome back!');
            }
        }

        return $next($request);
    }
}
