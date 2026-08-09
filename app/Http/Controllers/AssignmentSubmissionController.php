<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AssignmentSubmissionController extends Controller
{
    // Replaces the $page == 'teacher_submissions' branch: shows every
    // student submission for one assignment, with a form to mark each.
    // Ownership check (assignment must belong to this teacher) matches the
    // original's "SELECT * FROM assignments WHERE id = ? AND teacher_id = ?".
    public function index(Assignment $assignment): View
    {
        if ($assignment->teacher_id !== Auth::guard('teacher')->id()) {
            abort(403);
        }

        $submissions = $assignment->submissions()
            ->with('student')
            ->orderByDesc('submitted_at')
            ->get();

        return view('teacher.assignment-submissions', [
            'assignment' => $assignment,
            'submissions' => $submissions,
        ]);
    }

    // Replaces the mark_submission POST handler.
    public function mark(Request $request, AssignmentSubmission $submission): RedirectResponse
    {
        if ($submission->assignment->teacher_id !== Auth::guard('teacher')->id()) {
            abort(403);
        }

        $data = $request->validate([
            'marks' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string',
        ]);

        $submission->update([
            'marks' => $data['marks'],
            'feedback' => $data['feedback'] ?? null,
            'status' => 'marked',
        ]);

        return redirect()->route('teacher.assignments.submissions', $submission->assignment)
            ->with('status', 'Submission marked.');
    }
}
