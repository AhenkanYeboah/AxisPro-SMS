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

        // 2. Resolve school for tenant routes.
        //
        // Primary source of truth: the authenticated user's own school_id
        // (every Admin/Teacher/Student row has one - see their models).
        // This is what actually identifies which tenant a request belongs
        // to. `active_school_id` is read here too for any request that
        // deliberately sets it (e.g. a future subdomain resolver), but
        // nothing in the app currently writes that key, so don't rely on
        // it alone - if only this were checked, no logged-in user would
        // ever resolve a school.
        $school = null;

        foreach (['admin', 'teacher', 'student'] as $guard) {
            if (auth($guard)->check()) {
                $school = School::find(auth($guard)->user()->school_id);
                break;
            }
        }

        if (!$school && session()->has('active_school_id')) {
            $school = School::find(session('active_school_id'));
        }

        // Deliberately NO "School::first()" fallback here. Defaulting to
        // "whichever school happens to be row 1" silently makes every
        // unresolved request look like that school - which is how the
        // platform root page ended up rendering Royal Countryside Academy
        // in the first place. If nothing resolves, $school stays null and
        // downstream code (HomeController::index, BelongsToSchool, etc.)
        // treats that as "no tenant" rather than guessing.

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
