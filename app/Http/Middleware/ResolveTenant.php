<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\School;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Skip tenant resolution completely for central platform routes
        if ($this->isPlatformRoute($request)) {
            return $next($request);
        }

        // 2. Resolve school for tenant routes
        $school = null;

        // Check subdomain or session
        if (session()->has('active_school_id')) {
            $school = School::find(session('active_school_id'));
        } else {
            $school = School::first(); // Fallback default
        }

        if ($school) {
            // Share current school globally across views
            view()->share('currentSchool', $school);
            app()->instance('currentSchool', $school);
        }

        return $next($request);
    }

    /**
     * Determine if the current request belongs to central platform routes.
     */
    protected function isPlatformRoute(Request $request): bool
    {
        // Add any public/platform paths that should NEVER trigger school scoping
        return $request->is('/') 
            || $request->is('platform*') 
            || $request->is('signup*') 
            || $request->is('paystack*');
    }
}
