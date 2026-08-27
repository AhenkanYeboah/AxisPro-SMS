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
        try {
            // 1. Resolve school - try all methods, never crash
            $school = null;
            try {
                if (app()->bound('currentSchool')) {
                    $school = app('currentSchool');
                }
            } catch (Throwable $e) {}
            
            if (!$school && auth('admin')->check()) {
                try {
                    $admin = auth('admin')->user();
                    $school = $admin->school ?? ($admin->school_id ? School::find($admin->school_id) : null);
                } catch (Throwable $e) {}
            }

            // 2. Base query
            $query = Student::query();
            if ($school && $school->id) {
                $query->where('school_id', $school->id);
            }

            // 3. Get students safely
            try {
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
            } catch (Throwable $e) {
                $students = collect();
            }

            try {
                $recentApplicants = (clone $query)->orderByDesc('created_at')->limit(10)->get();
            } catch (Throwable $e) {
                $recentApplicants = collect();
            }

            try {
                $classRoster = (clone $query)
                    ->where('status', 'active')
                    ->orderBy('class')
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->get()
                    ->groupBy(fn ($student) => $student->class ?: 'Not Specified');
            } catch (Throwable $e) {
                $classRoster = collect();
            }

            try {
                $stats = StudentStats::compute();
            } catch (Throwable $e) {
                $stats = (object) [
                    'total' => $students->count(),
                    'active' => $students->where('status', 'active')->count(),
                    'pending' => $students->where('admission_status', 'pending')->count(),
                    'admitted' => $students->where('admission_status', 'admitted')->count(),
                ];
            }

            try {
                $activitiesQuery = SchoolActivity::query();
                if ($school && $school->id) {
                    $activitiesQuery->where('school_id', $school->id);
                }
                $allActivities = $activitiesQuery->orderBy('activity_date')->get();
            } catch (Throwable $e) {
                $allActivities = collect();
            }

            return view('admin.dashboard', compact('students', 'recentApplicants', 'classRoster', 'stats', 'allActivities', 'school'));

        } catch (Throwable $e) {
            // FINAL FALLBACK - never 500
            $school = null;
            try { $school = School::first(); } catch (Throwable $ex) {}
            return view('admin.dashboard', [
                'students' => collect(),
                'recentApplicants' => collect(),
                'classRoster' => collect(),
                'stats' => (object)['total'=>0,'active'=>0,'pending'=>0,'admitted'=>0],
                'allActivities' => collect(),
                'school' => $school,
                'error' => $e->getMessage(),
            ]);
        }
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
