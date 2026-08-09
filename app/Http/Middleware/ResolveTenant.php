<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    // Runs before routing on every web request. Pulls the subdomain off the
    // request host, finds the matching School, and binds it into the app
    // container as 'currentSchool' - every model using BelongsToSchool then
    // automatically scopes its queries to that school for the rest of the
    // request. Nothing downstream needs to know how the school was resolved.
    // Paths that exist outside any single school's context - signup (a
    // school doesn't exist yet), the platform-owner panel (spans ALL
    // schools), and the Paystack webhook (server-to-server, no subdomain to
    // resolve against). Without this, ResolveTenant would 404 or wrongly
    // scope these in production, same issue flagged for /signup earlier.
    private const EXEMPT_PATH_PATTERNS = [
        'signup',
        'signup/*',
        'platform',
        'platform/*',
        'paystack/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is(self::EXEMPT_PATH_PATTERNS)) {
            return $next($request);
        }

        $school = $this->resolveSchool($request);

        if (!$school) {
            // The homepage is the one page allowed to exist without a
            // resolved school - it's how AxisPro's own central landing page
            // (as opposed to any one customer's homepage) gets shown when a
            // domain doesn't match any school's subdomain. Every other
            // route still 404s on an unmatched subdomain, same as before.
            if ($request->is('/')) {
                return $next($request);
            }

            abort(404, "We couldn't find a school at this address.");
        }

        if (!$school->isActive()) {
            abort(403, 'This school\'s account is currently suspended. Please contact support.');
        }

        app()->instance('currentSchool', $school);

        // Also share it with every view, so layouts can show the school's
        // own name/logo instead of a hardcoded one.
        view()->share('currentSchool', $school);

        return $next($request);
    }

    private function resolveSchool(Request $request): ?School
    {
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0] ?? null;

        $school = $subdomain ? School::where('subdomain', $subdomain)->first() : null;

        if ($school) {
            return $school;
        }

        // Local development convenience: `php artisan serve` is reached at
        // 127.0.0.1/localhost, which has no subdomain to resolve. Rather
        // than requiring local DNS/hosts-file setup for every developer,
        // fall back to the first seeded school ONLY outside production.
        // This fallback intentionally never runs in production - a
        // mismatched domain there should 404 (or show the central
        // homepage, for '/' - see handle() above), not silently serve
        // school #1.
        //
        // Root '/' is deliberately excluded from this fallback even in dev,
        // so visiting it locally shows the same AxisPro central homepage
        // production would show for an unmatched domain - the one route
        // where local behavior matching production actually matters, since
        // it's otherwise impossible to preview that page without real
        // subdomain DNS. Every other route still gets the fallback below.
        if (!app()->environment('production') && !$request->is('/')) {
            return School::query()->oldest('id')->first();
        }

        return null;
    }
}
