<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\School;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Skip tenant resolution for admin portal, API routes, or global endpoints
        if ($request->is('admin*') || $request->is('api*')) {
            return $next($request);
        }

        // 2. Extract tenant slug from path segment: /school/{slug}/*
        $slug = $request->segment(2); // Get second URL segment

        if ($request->segment(1) === 'school' && $slug) {
            $school = School::where('slug', $slug)->first();

            if (!$school) {
                abort(404, 'School tenant not found.');
            }

            // Bind current school instance to Laravel Container & Session
            app()->instance('currentSchool', $school);
            session(['current_school_id' => $school->id]);
        }

        return $next($request);
    }
}
