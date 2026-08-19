<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\School;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        // Exempt central system paths from tenant resolution
        if (str_starts_with($path, 'platform') || $path === 'signup' || $path === '/') {
            return $next($request);
        }

        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];

        // Dev fallback or subdomain matching
        $school = School::where('slug', $subdomain)->first();

        if (!$school && config('app.env') === 'local') {
            $school = School::first();
        }

        if (!$school) {
            // Fall back to first school if visiting main domain directly (e.g. on Render)
            $school = School::first();
        }

        if (!$school) {
            abort(404, 'School tenant not resolved.');
        }

        session(['active_school_id' => $school->id]);
        app()->instance('currentSchool', $school);
        view()->share('currentSchool', $school);

        return $next($request);
    }
}
