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
        if ($request->is('platform*') || $request->is('paystack*') || $request->is('signup*')) {
            return $next($request);
        }

        $school = null;

        // Try to get school from logged-in user safely
        foreach (['admin','teacher','student'] as $guard) {
            try {
                if (auth($guard)->check()) {
                    $user = auth($guard)->user();
                    if ($user && !empty($user->school_id)) {
                        $school = School::find($user->school_id);
                        if ($school) break;
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        if (!$school && session()->has('active_school_id')) {
            try { $school = School::find(session('active_school_id')); } catch (\Throwable $e) {}
        }

        if ($school) {
            view()->share('currentSchool', $school);
            app()->instance('currentSchool', $school);
        }

        return $next($request);
    }
}
