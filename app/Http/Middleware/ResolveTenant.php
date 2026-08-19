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
        // 1. Explicitly bypass platform admin routes
        if ($request->is('platform*') || $request->is('api*')) {
            return $next($request);
        }

        $school = null;

        // 2. Extract tenant slug from URL: /school/{slug}/*
        if ($request->segment(1) === 'school' && $request->segment(2)) {
            $slug = $request->segment(2);
            $school = School::where('slug', $slug)->orWhere('subdomain', $slug)->first();

            if (!$school) {
                abort(404, 'School tenant not found.');
            }
        } 
        // 3. Fallback for /admin/* routes: resolve school from authenticated admin or session
        elseif (auth('admin')->check()) {
            $admin = auth('admin')->user();
            if (isset($admin->school_id)) {
                $school = School::find($admin->school_id);
            } elseif (method_exists($admin, 'school')) {
                $school = $admin->school;
            }
        } elseif (session()->has('current_school_id')) {
            $school = School::find(session('current_school_id'));
        }

        // 4. Bind resolved school globally into the container and session
        if ($school) {
            app()->instance('currentSchool', $school);
            app()->instance(School::class, $school);
            session(['current_school_id' => $school->id]);
        }

        return $next($request);
    }
}
