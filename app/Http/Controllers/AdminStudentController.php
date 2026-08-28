<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\School;
use App\Models\SchoolActivity;
use App\Models\Student;
use App\Support\StudentStats;
use App\Mail\ExamScheduledMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class AdminStudentController extends Controller
{
    public function dashboard(Request $request)
    {
        try {
            $school = null;
            try { if (app()->bound('currentSchool')) $school = app('currentSchool'); } catch (Throwable $e) {}
            if (!$school && auth('admin')->check()) {
                $admin = auth('admin')->user();
                $school = $admin->school ?? ($admin->school_id ? School::find($admin->school_id) : null);
            }

            $query = Student::query();
            if ($school && isset($school->id)) $query->where('school_id', $school->id);

            $students = collect(); $recentApplicants = collect(); $classRoster = collect(); $allActivities = collect();
            try { $students = (clone $query)->orderByDesc('created_at')->get(); } catch (Throwable $e) {}
            try { $recentApplicants = (clone $query)->orderByDesc('created_at')->limit(10)->get(); } catch (Throwable $e) {}
            try { $classRoster = (clone $query)->where('status','active')->orderBy('class')->get()->groupBy(fn($s)=>$s->class ?: 'Not Specified'); } catch (Throwable $e) {}

            try { $stats = StudentStats::compute(); }
            catch (Throwable $e) { $stats = ['total'=>0,'admitted'=>0,'pending'=>0,'male'=>0,'female'=>0,'by_class'=>collect(),'by_region'=>collect()]; }
            $stats = is_array($stats) ? $stats : (array)$stats;

            try {
                $activitiesQuery = SchoolActivity::query();
                if ($school && isset($school->id)) $activitiesQuery->where('school_id',$school->id);
                $allActivities = $activitiesQuery->orderBy('activity_date')->get();
            } catch (Throwable $e) {}

            return view('admin.dashboard', compact('students','recentApplicants','classRoster','stats','allActivities','school'));
        } catch (Throwable $e) {
            return view('admin.dashboard', [
                'students'=>collect(),'recentApplicants'=>collect(),'classRoster'=>collect(),
                'stats'=>['total'=>0,'admitted'=>0,'pending'=>0,'male'=>0,'female'=>0,'by_class'=>collect(),'by_region'=>collect()],
                'allActivities'=>collect(),'school'=>School::first(),'error'=>$e->getMessage()
            ]);
        }
    }

    public function show(Student $student): View
    {
        $exams = Exam::orderByDesc('created_at')->get(['id','title']);
        $examSubmission = $student->examSubmission;
        return view('admin.view_student', compact('student','exams','examSubmission'));
    }

    public function setExamDate(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate(['exam_date'=>'required|date','exam_id'=>'nullable|exists:exams,id']);
        $student->update(['exam_date'=>$data['exam_date'],'exam_id'=>$data['exam_id'] ?? null]);
        if (!empty($data['exam_id']) && $student->email) Mail::to($student->email)->send(new ExamScheduledMail($student->fresh()));
        return redirect()->route('admin.students.show',$student);
    }

    public function markExamCompleted(Student $student): RedirectResponse { $student->update(['exam_completed'=>true]); return redirect()->route('admin.students.show',$student); }
    public function verify(Student $student): RedirectResponse { $student->update(['status'=>'active','admission_status'=>'admitted']); return redirect()->route('admin.students.show',$student); }
    public function decline(Student $student): RedirectResponse { $student->update(['status'=>'declined','admission_status'=>'undecided']); return redirect()->route('admin.students.show',$student); }
    public function destroy(Student $student): RedirectResponse { $student->delete(); return redirect()->route('admin.dashboard')->with('status','Student deleted.'); }
}
