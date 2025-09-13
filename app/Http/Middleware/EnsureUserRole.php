<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * This middleware ensures users access only routes appropriate to their role:
     * - Admins can access admin, manager, and employee routes
     * - Managers can access manager and employee routes but not admin routes
     * - Employees can only access employee routes
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $currentRoute = $request->route()->getName();
        $isAdminRoute = str_starts_with($currentRoute, 'admin.');
        $isManagerRoute = str_starts_with($currentRoute, 'manager.');
        $isEmployeeRoute = ! $isAdminRoute && ! $isManagerRoute && ! str_starts_with($currentRoute, 'auth.');

        // Check if user is trying to access admin routes
        if ($isAdminRoute) {
            if (! $user->can('access-admin-panel')) {
                // Redirect non-admin users to their appropriate dashboard
                if ($user->hasRole('manager')) {
                    return redirect()->route('manager.dashboard')->with('error', 'Access denied. Admin permissions required.');
                }

                return redirect()->route('dashboard')->with('error', 'Access denied. Admin permissions required.');
            }
        }

        // Check if user is trying to access manager routes
        if ($isManagerRoute) {
            if (! $user->hasRole(['admin', 'manager'])) {
                return redirect()->route('dashboard')->with('error', 'Access denied. Manager permissions required.');
            }
        }

        // Check if non-employees are trying to access employee-only routes
        // Currently allowing all roles to access employee routes for flexibility

        return $next($request);
    }
}
