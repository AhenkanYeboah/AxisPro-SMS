<?php

namespace App\Http\Controllers;

use App\Mail\ExamScheduledMail;
use App\Models\Exam;
use App\Models\Student;
use App\Support\StudentStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminStudentController extends Controller
{
    // Replaces the hand-built $where/$params/$types block. Eloquent's when()
    // only adds a clause when the condition is true - same effect, far less code,
    // and query parameters are bound automatically (no manual "types" string).
    public function dashboard(Request $request): View
    {
        $students = Student::query()
            ->when($request->filled('name'), function ($query) use ($request) {
                $search = $request->input('name');
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('admission_status'), fn ($query) => $query->where('admission_status', $request->input('admission_status')))
            ->when($request->filled('gender'), fn ($query) => $query->where('gender', $request->input('gender')))
            ->when($request->filled('class'), fn ($query) => $query->where('class', $request->input('class')))
            ->orderByDesc('created_at')
            ->get();

        $recentApplicants = Student::orderByDesc('created_at')->limit(10)->get();

        $classRoster = Student::where('status', 'active')
            ->orderBy('class')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->groupBy(fn ($student) => $student->class ?: 'Not Specified');

        $stats = StudentStats::compute();
        $allActivities = \App\Models\SchoolActivity::orderBy('activity_date')->get();

        return view('admin.dashboard', compact('students', 'recentApplicants', 'classRoster', 'stats', 'allActivities'));
    }

    public function show(Student $student): View
    {
        $exams = Exam::orderByDesc('created_at')->get(['id', 'title']);
        $examSubmission = $student->examSubmission;

        return view('admin.view_student', compact('student', 'exams', 'examSubmission'));
    }

    // Replaces the admin_action switch statement (set_exam_date, mark_exam_completed,
    // verify_student, decline_student) - one method per action keeps things
    // readable and each gets its own named route.
    public function setExamDate(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate([
            'exam_date' => 'required|date',
            'exam_id' => 'nullable|exists:exams,id',
        ]);

        $student->update([
            'exam_date' => $data['exam_date'],
            'exam_id' => $data['exam_id'] ?? null,
        ]);

        // Only notify if an actual exam was attached - a date with no exam
        // yet isn't something the applicant can act on.
        if (!empty($data['exam_id']) && $student->email) {
            Mail::to($student->email)->send(new ExamScheduledMail($student->fresh()));
        }

        return redirect()->route('admin.students.show', $student);
    }

    public function markExamCompleted(Student $student): RedirectResponse
    {
        $student->update(['exam_completed' => true]);

        return redirect()->route('admin.students.show', $student);
    }

    public function verify(Student $student): RedirectResponse
    {
        $student->update(['status' => 'active', 'admission_status' => 'admitted']);

        return redirect()->route('admin.students.show', $student);
    }

    public function decline(Student $student): RedirectResponse
    {
        $student->update(['status' => 'declined', 'admission_status' => 'undecided']);

        return redirect()->route('admin.students.show', $student);
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        return redirect()->route('admin.dashboard')->with('status', 'Student deleted.');
    }
}
