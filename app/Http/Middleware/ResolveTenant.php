<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Paths that do not require a school context.
     */
    private const EXEMPT_PATH_PATTERNS = [
        '/',
        'signup',
        'signup/*',
        'platform',
        'platform/*',
        'paystack/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Skip tenant resolution for central/exempt routes
        if ($request->is(self::EXEMPT_PATH_PATTERNS)) {
            return $next($request);
        }

        $school = $this->resolveSchool($request);

        if (!$school) {
            abort(404, "We couldn't find a school at this address.");
        }

        if (!$school->isActive()) {
            abort(403, 'This school\'s account is currently suspended. Please contact support.');
        }

        app()->instance('currentSchool', $school);

        // Share the current school instance across all Blade templates
        view()->share('currentSchool', $school);

        return $next($request);
    }

    private function resolveSchool(Request $request): ?School
    {
        // 1. Try resolving via Route Model Binding if route uses {school} or {school:slug}
        $routeSchool = $request->route('school');

        if ($routeSchool instanceof School) {
            return $routeSchool;
        }

        if (is_string($routeSchool)) {
            $school = School::where('slug', $routeSchool)
                ->orWhere('subdomain', $routeSchool)
                ->first();

            if ($school) {
                return $school;
            }
        }

        // 2. Try resolving directly from path prefix (e.g., /school/{slug}/...)
        $slug = $request->segment(2); // 'school' is segment 1, {slug} is segment 2

        if ($slug) {
            $school = School::where('slug', $slug)
                ->orWhere('subdomain', $slug)
                ->first();

            if ($school) {
                return $school;
            }
        }

        // 3. Local development fallback (outside production)
        if (!app()->environment('production')) {
            return School::query()->oldest('id')->first();
        }

        return null;
    }
}
