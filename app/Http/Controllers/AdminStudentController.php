<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Support\StudentStats;
use Illuminate\Http\Request;
use Throwable;
use Illuminate\Support\Facades\Log;

class AdminStudentController extends Controller
{
    public function dashboard(Request $request)
    {
        try {
            // Stats - safe
            try {
                $stats = StudentStats::compute();
                $stats = is_array($stats) ? $stats : (array) $stats;
            } catch (Throwable $e) {
                Log::error('Stats failed: '.$e->getMessage());
                $stats = ['total'=>0,'admitted'=>0,'pending'=>0,'male'=>0,'female'=>0,'by_region'=>collect(),'by_class'=>collect()];
            }
            $stats['total'] = $stats['total'] ?? 0;
            $stats['admitted'] = $stats['admitted'] ?? 0;
            $stats['pending'] = $stats['pending'] ?? 0;
            $stats['male'] = $stats['male'] ?? 0;
            $stats['female'] = $stats['female'] ?? 0;
            $stats['by_region'] = $stats['by_region'] ?? collect();
            $stats['by_class'] = $stats['by_class'] ?? collect();

            // School
            $school = null;
            if (app()->bound('currentSchool')) {
                $school = app('currentSchool');
            }

            // Students query - unscoped to avoid tenant filter issues
            $query = Student::withoutGlobalScope('school')->with('examSubmission');

            if ($school && isset($school->id)) {
                $query->where('school_id', $school->id);
            }

            if ($request->filled('name')) {
                $name = trim((string) $request->input('name'));
                $query->where(function($q) use ($name) {
                    $q->where('first_name','ilike',"%{$name}%")
                      ->orWhere('last_name','ilike',"%{$name}%")
                      ->orWhere('other_names','ilike',"%{$name}%");
                });
            }
            if ($request->filled('admission_status')) {
                $query->where('admission_status', $request->input('admission_status'));
            }
            if ($request->filled('gender')) {
                $query->where('gender', $request->input('gender'));
            }
            if ($request->filled('class')) {
                $query->where('class', $request->input('class'));
            }

            $students = $query->latest()->limit(200)->get();
            $classRoster = $students->where('admission_status','admitted')->groupBy('class');
            $recentApplicants = $students->take(8);
            $allActivities = collect();

            return view('admin.dashboard', compact('stats','students','classRoster','recentApplicants','allActivities','school'));

        } catch (Throwable $e) {
            Log::error('Dashboard crash: '.$e->getMessage().' '.$e->getFile().':'.$e->getLine());
            return response()->make("
                <html><body style='font-family:sans-serif;padding:40px'>
                <h2 style='color:#b00'>Dashboard Error (debug)</h2>
                <p><b>{$e->getMessage()}</b></p>
                <p>{$e->getFile()}:{$e->getLine()}</p>
                <pre style='background:#f5f5f5;padding:16px;overflow:auto'>{$e->getTraceAsString()}</pre>
                <p><a href='/admin/login'>Back to login</a></p>
                </body></html>", 500);
        }
    }

    public function show(Student $student) {
        $student->load('examSubmission');
        $exams = \App\Models\Exam::orderByDesc('created_at')->get(['id','title']);
        $examSubmission = $student->examSubmission;
        return view('admin.view_student', compact('student','exams','examSubmission'));
    }
    public function setExamDate(Request $request, Student $student) {
        $data = $request->validate(['exam_date'=>'required|date','exam_id'=>'nullable|exists:exams,id']);
        $student->update(['exam_date'=>$data['exam_date'],'exam_id'=>$data['exam_id'] ?? null]);
        return redirect()->route('admin.students.show',$student);
    }
    public function markExamCompleted(Student $student) {
        $student->update(['exam_completed'=>true]);
        return redirect()->route('admin.students.show',$student);
    }
    public function verify(Student $student) {
        $student->update(['status'=>'active','admission_status'=>'admitted']);
        return redirect()->route('admin.students.show',$student);
    }
    public function decline(Student $student) {
        $student->update(['status'=>'declined','admission_status'=>'undecided']);
        return redirect()->route('admin.students.show',$student);
    }
    public function destroy(Student $student) {
        $student->delete();
        return redirect()->route('admin.dashboard')->with('status','Student deleted.');
    }
}
