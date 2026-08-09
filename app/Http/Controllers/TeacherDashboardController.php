<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Student;
use App\Support\StudentStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeacherDashboardController extends Controller
{
    // Same class progression ladder as the original file. JHS 3 has no
    // further promotion (maps to itself), matching the original's comment.
    private const NEXT_CLASS_MAPPING = [
        'Creche' => 'Nursery 1',
        'Nursery 1' => 'Nursery 2',
        'Nursery 2' => 'Kindergarten 1',
        'Kindergarten 1' => 'Kindergarten 2',
        'Kindergarten 2' => 'Primary 1',
        'Primary 1' => 'Primary 2',
        'Primary 2' => 'Primary 3',
        'Primary 3' => 'Primary 4',
        'Primary 4' => 'Primary 5',
        'Primary 5' => 'Primary 6',
        'Primary 6' => 'JHS 1',
        'JHS 1' => 'JHS 2',
        'JHS 2' => 'JHS 3',
        'JHS 3' => 'JHS 3',
    ];

    public function index(): View
    {
        $teacher = Auth::guard('teacher')->user();
        $stats = StudentStats::computeForClass($teacher->assigned_class);

        $classRoster = Student::where('status', 'active')
            ->where('class', $teacher->assigned_class)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $male = $classRoster->where('gender', 'Male');
        $female = $classRoster->where('gender', 'Female');

        $assignments = Assignment::where('teacher_id', $teacher->id)
            ->orderByDesc('due_date')
            ->get();

        return view('teacher.dashboard', compact('teacher', 'stats', 'classRoster', 'male', 'female', 'assignments'));
    }

    // Replaces the ?promote=1&student_id=... GET handler. A student can only
    // be promoted by the teacher currently assigned to their class - same
    // authorization check as the original, just expressed as an early return.
    public function promote(Student $student): RedirectResponse
    {
        $teacher = Auth::guard('teacher')->user();

        if ($student->status !== 'active') {
            return redirect()->route('teacher.dashboard')->with('promotion_error', 'Student not found or not active.');
        }

        if ($student->class !== $teacher->assigned_class) {
            return redirect()->route('teacher.dashboard')->with('promotion_error', 'You are not authorized to promote this student.');
        }

        $nextClass = self::NEXT_CLASS_MAPPING[$student->class] ?? $student->class;

        if ($nextClass === $student->class) {
            return redirect()->route('teacher.dashboard')->with('promotion_info', 'Student is already in the highest class. Cannot promote further.');
        }

        $student->update(['class' => $nextClass]);

        return redirect()->route('teacher.dashboard')->with('promotion_success', "Student promoted to {$nextClass} successfully!");
    }

    // Replaces the ?repeat=1&student_id=... handler - the original didn't
    // actually change anything in the database here, just showed a message.
    public function repeat(Student $student): RedirectResponse
    {
        return redirect()->route('teacher.dashboard')->with('promotion_info', 'Student will remain in the same class.');
    }
}
