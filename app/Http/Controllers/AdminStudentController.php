<?php

namespace App\Http\Controllers;

use App\Mail\ExamScheduledMail;
use App\Models\Exam;
use App\Models\School;
use App\Models\SchoolActivity;
use App\Models\Student;
use App\Support\StudentStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class AdminStudentController extends Controller
{
    public function dashboard(Request $request): View|RedirectResponse
    {
        // 1. Resolve current school safely to prevent 500 crashes
        $school = app()->bound('currentSchool') ? app('currentSchool') : null;

        if (!$school && auth('admin')->check()) {
            $school = auth('admin')->user()->school;
        }

        if (!$school && session()->has('active_school_id')) {
            $school = School::find(session('active_school_id'));
        }

        // 2. Base Query scoped to current school if available
        $query = Student::query();
        if ($school) {
            $query->where('school_id', $school->id);
        }

        $students = (clone $query)
            ->when($request->filled('name'), function ($q) use ($request) {
                $search = $request->input('name');
                $q->where(function ($inner) use ($search) {
                    $inner->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('admission_status'), fn ($q) => $q->where('admission_status', $request->input('admission_status')))
            ->when($request->filled('gender'), fn ($q) => $q->where('gender', $request->input('gender')))
            ->when($request->filled('class'), fn ($q) => $q->where('class', $request->input('class')))
            ->orderByDesc('created_at')
            ->get();

        $recentApplicants = (clone $query)->orderByDesc('created_at')->limit(10)->get();

        $classRoster = (clone $query)
            ->where('status', 'active')
            ->orderBy('class')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->groupBy(fn ($student) => $student->class ?: 'Not Specified');

        // 3. Wrap StudentStats in a try-catch to prevent dashboard crash on calculation errors
        try {
            $stats = StudentStats::compute();
        } catch (Throwable $e) {
            $stats = (object) [
                'total' => $students->count(),
                'active' => $students->where('status', 'active')->count(),
                'pending' => $students->where('admission_status', 'pending')->count(),
            ];
        }

        // 4. Safely query activities
        try {
            $activitiesQuery = SchoolActivity::query();
            if ($school) {
                $activitiesQuery->where('school_id', $school->id);
            }
            $allActivities = $activitiesQuery->orderBy('activity_date')->get();
        } catch (Throwable $e) {
            $allActivities = collect();
        }

        return view('admin.dashboard', compact('students', 'recentApplicants', 'classRoster', 'stats', 'allActivities', 'school'));
    }

    public function show(Student $student): View
    {
        $exams = Exam::orderByDesc('created_at')->get(['id', 'title']);
        $examSubmission = $student->examSubmission;

        return view('admin.view_student', compact('student', 'exams', 'examSubmission'));
    }

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
