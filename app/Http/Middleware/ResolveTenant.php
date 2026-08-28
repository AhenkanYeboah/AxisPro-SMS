<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\School;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if ($this->isPlatformRoute($request)) {
                return $next($request);
            }

            $school = null;

            try {
                foreach (['admin', 'teacher', 'student'] as $guard) {
                    if (auth($guard)->check()) {
                        $user = auth($guard)->user();
                        if ($user && !empty($user->school_id)) {
                            $school = School::find($user->school_id);
                            if ($school) break;
                        }
                    }
                }
            } catch (Throwable $e) {
                // auth check failed - ignore
            }

            try {
                if (!$school && session()->has('active_school_id')) {
                    $id = session('active_school_id');
                    if ($id) $school = School::find($id);
                }
            } catch (Throwable $e) {}

            if ($school) {
                try {
                    view()->share('currentSchool', $school);
                    app()->instance('currentSchool', $school);
                } catch (Throwable $e) {}
            }

            return $next($request);
        } catch (Throwable $e) {
            // NEVER let tenant resolution kill the request
            \Illuminate\Support\Facades\Log::error('ResolveTenant crash: '.$e->getMessage().' '.$e->getFile().':'.$e->getLine());
            return $next($request);
        }
    }

    protected function isPlatformRoute(Request $request): bool
    {
        return $request->is('/')
            || $request->is('platform*')
            || $request->is('signup*')
            || $request->is('paystack*')
            || $request->is('admin/login')
            || $request->is('admin/logout');
    }
}
