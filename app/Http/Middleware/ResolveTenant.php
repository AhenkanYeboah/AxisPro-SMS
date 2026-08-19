<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\School; // Replace with your Tenant model if different

class ResolveTenant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $centralDomains = [
            config('app.url'),
            'localhost',
            '127.0.0.1',
            // Add your main production domain here (e.g., 'platform.com')
        ];

        $host = parse_url($request->getHttpHost(), PHP_URL_HOST) ?? $request->getHost();

        // Check if the current host matches central domains or explicit central routes
        if (in_array($host, $centralDomains, true) || $request->is('admin*') || $request->is('platform*')) {
            // Bypass tenant resolution and clear any active tenant context
            app()->forgetInstance('currentTenant');
            return $next($request);
        }

        // Proceed with standard tenant resolution logic
        $tenant = School::where('domain', $host)
            ->orWhere('slug', explode('.', $host)[0])
            ->first();

        if (!$tenant) {
            abort(404, 'School or Tenant not found.');
        }

        // Bind resolved tenant to application context
        app()->instance('currentTenant', $tenant);

        return $next($request);
    }
}
