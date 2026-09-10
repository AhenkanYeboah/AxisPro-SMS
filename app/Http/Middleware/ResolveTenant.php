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
        // Platform routes never get a tenant bound - MUST return early
        if ($request->is('platform*') || $request->is('paystack*') || $request->is('signup*') || $request->is('storage*')) {
            view()->share('currentSchool', null);
            app()->instance('currentSchool', null);
            return $next($request);
        }

        $school = null;

        // Only try to resolve from auth if user is already authenticated
        // Skip for login routes themselves - they have no user yet
        if (!$request->is('*/login') && !$request->is('login')) {
            foreach (['admin', 'teacher', 'student'] as $guard) {
                try {
                    if (auth($guard)->check()) {
                        $user = auth($guard)->user();
                        if ($user && !empty($user->school_id)) {
                            $school = School::find($user->school_id);
                            if ($school) {
                                break;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::warning("ResolveTenant guard {$guard} failed: ".$e->getMessage());
                    continue;
                }
            }

            if (!$school && session()->has('active_school_id')) {
                try {
                    $id = session('active_school_id');
                    if (is_numeric($id)) {
                        $school = School::find($id);
                    }
                } catch (\Throwable $e) {}
            }
        }

        // ALWAYS share - even if null - prevents Undefined variable in Blade
        view()->share('currentSchool', $school);
        app()->instance('currentSchool', $school);
        
        if ($school) {
            session(['active_school_id' => $school->id]);
        }

        return $next($request);
    }
}
