<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Confirmed gap, now closed: an applicant who submits the public
 * enrollment form (StudentAuthController::register) sets a password
 * immediately afterward and is auto-logged in - but at that point nothing
 * has been decided about their admission. Every route in the auth:student
 * group used to be reachable at that stage, meaning an unreviewed
 * applicant had the exact same access as an admitted student: dashboard,
 * assignments, timetable, report card, fees - the works.
 *
 * The entrance exam is deliberately NOT gated by this middleware - an
 * applicant taking their entrance exam is exactly the pre-admission
 * access they're supposed to have (see routes/web.php, where
 * student.exam/student.exam.submit sit outside this middleware's group).
 * Everything else - the actual student portal - only opens once an admin
 * has explicitly admitted them (AdminStudentController::admit(), which
 * sets status='active' AND admission_status='admitted' together).
 */
class EnsureStudentAdmitted
{
    public function handle(Request $request, Closure $next): Response
    {
        $student = Auth::guard('student')->user();

        if ($student && $student->status !== 'active') {
            return redirect()->route('student.application-status');
        }

        return $next($request);
    }
}
