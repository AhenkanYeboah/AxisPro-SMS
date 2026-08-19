<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\School;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $host = parse_url($request->getHttpHost(), PHP_URL_HOST) ?? $request->getHost();

        // 1. Central domains that MUST bypass tenant resolution
        $centralDomains = [
            'localhost',
            '127.0.0.1',
            'axispro-sms.onrender.com', // Your primary Render domain
            parse_url(config('app.url'), PHP_URL_HOST),
        ];

        // 2. Bypass tenant resolution for central host or explicitly un-scoped routes
        if (in_array($host, array_filter($centralDomains), true) || $request->is('platform*')) {
            app()->forgetInstance('currentTenant');
            return $next($request);
        }

        // 3. Extract potential subdomain/slug (e.g. "school1" from "school1.axispro-sms.onrender.com")
        $slug = explode('.', $host)[0];

        // 4. Query using actual schema column names: 'subdomain' or 'slug'
        $tenant = School::where('subdomain', $host)
            ->orWhere('subdomain', $slug)
            ->orWhere('slug', $slug)
            ->first();

        if (!$tenant) {
            abort(404, 'School or Tenant not found.');
        }

        // Bind resolved tenant to application context
        app()->instance('currentTenant', $tenant);

        return $next($request);
    }
}
