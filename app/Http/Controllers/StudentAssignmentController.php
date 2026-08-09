<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentAssignmentController extends Controller
{
    // Replaces the $page == 'student_assignments' branch: lists assignments
    // for the student's class, each annotated with whether/how the student
    // has already submitted, so the view can show the right form/state.
    public function index(): View
    {
        $student = Auth::guard('student')->user();

        $assignments = $student->class
            ? Assignment::where('class', $student->class)
                ->orderByDesc('created_at')
                ->get()
            : collect();

        $submissions = AssignmentSubmission::where('student_id', $student->id)
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->get()
            ->keyBy('assignment_id');

        return view('student.assignments', [
            'student' => $student,
            'assignments' => $assignments,
            'submissions' => $submissions,
        ]);
    }

    // Replaces the submit_assignment POST handler: blocks submission after
    // the due date and blocks a second submission for the same assignment,
    // same two checks the original ran before inserting.
    public function submit(Request $request, Assignment $assignment): RedirectResponse
    {
        $student = Auth::guard('student')->user();

        if ($assignment->class !== $student->class) {
            abort(403);
        }

        if ($assignment->isPastDue()) {
            return redirect()->route('student.assignments')
                ->with('error', 'The due date for this assignment has passed.');
        }

        $alreadySubmitted = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->exists();

        if ($alreadySubmitted) {
            return redirect()->route('student.assignments')
                ->with('error', 'You have already submitted this assignment.');
        }

        $data = $request->validate([
            'submission_file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,xls,xlsx|max:5120',
        ]);

        $path = $request->file('submission_file')->store('uploads/submissions', 'public');

        AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'submission_file' => $path,
            'status' => 'submitted',
        ]);

        return redirect()->route('student.assignments')->with('status', 'Assignment submitted successfully.');
    }

    // Replaces the $page == 'student_submission_view' branch ("My Results"):
    // a flat list of every submission the student has made, across all
    // assignments, newest submission first - not scoped to one assignment
    // like index() above.
    public function results(): View
    {
        $student = Auth::guard('student')->user();

        $submissions = AssignmentSubmission::where('student_id', $student->id)
            ->with('assignment')
            ->orderByDesc('submitted_at')
            ->get();

        return view('student.results', [
            'student' => $student,
            'submissions' => $submissions,
        ]);
    }
}
