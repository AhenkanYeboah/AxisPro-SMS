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
        // Never resolve tenant for true platform routes
        if ($request->is('platform*') || $request->is('signup*') || $request->is('paystack*')) {
            return $next($request);
        }

        $school = null;

        // 1. From logged-in user (admin/teacher/student) - PRIMARY
        foreach (['admin', 'teacher', 'student'] as $guard) {
            if (auth($guard)->check()) {
                $uid = auth($guard)->user()->school_id ?? null;
                if ($uid) {
                    $school = School::find($uid);
                    if ($school) break;
                }
            }
        }

        // 2. From session (if user explicitly switched school)
        if (!$school && session()->has('active_school_id')) {
            $school = School::find(session('active_school_id'));
        }

        // 3. Bind if found
        if ($school) {
            view()->share('currentSchool', $school);
            app()->instance('currentSchool', $school);
            // ALSO store in session so /school-home remembers it
            session(['active_school_id' => $school->id]);
        }

        return $next($request);
    }
}
