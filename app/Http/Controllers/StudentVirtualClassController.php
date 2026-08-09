<?php

namespace App\Http\Controllers;

use App\Models\VirtualClass;
use App\Models\VirtualClassAttendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Student-facing virtual class access - deliberately sits behind the same
 * 'admitted' middleware as the rest of the real student portal (see
 * routes/web.php), not the exam-taking exemption. A prospective applicant
 * has no business joining a live class before being admitted - this is
 * exactly the access EnsureStudentAdmitted exists to gate.
 */
class StudentVirtualClassController extends Controller
{
    public function index(): View
    {
        $student = Auth::guard('student')->user();

        $classes = $student->classLevel
            ? VirtualClass::where('class_level_id', $student->classLevel->id)
                ->where('status', 'scheduled')
                ->orderBy('scheduled_start')
                ->get()
            : collect();

        return view('student.virtual-classes', [
            'student' => $student,
            'classes' => $classes,
        ]);
    }

    // Records attendance (best-effort - see the virtual_class_attendance
    // migration comment on what "attendance" means here) then sends the
    // student straight to the external join_url. A GET rather than a POST
    // specifically so it can be a plain link/button, not a form.
    public function join(VirtualClass $virtualClass): RedirectResponse
    {
        $student = Auth::guard('student')->user();

        abort_unless($virtualClass->class_level_id === $student->class_level_id, 403);
        abort_if($virtualClass->status === 'cancelled', 410, 'This class has been cancelled.');

        VirtualClassAttendance::firstOrCreate(
            ['virtual_class_id' => $virtualClass->id, 'student_id' => $student->id],
            ['joined_at' => now()]
        );

        return redirect()->away($virtualClass->join_url);
    }
}
