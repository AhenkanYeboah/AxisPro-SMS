<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Student;
use App\Support\StudentStats;
use Throwable;

class AdminStudentController extends Controller
{
    public function dashboard(\Illuminate\Http\Request $request)
    {
        try {
            $stats = StudentStats::compute();
            $stats = is_array($stats) ? $stats : (array)$stats;
            $total = $stats['total'] ?? 0;
            $admitted = $stats['admitted'] ?? 0;
            $students = Student::limit(5)->get(['first_name','last_name','email']);
            
            return response()->make("
                <html><body style='font-family:sans-serif;padding:40px'>
                <h1 style='color:green'>✅ DASHBOARD WORKS!</h1>
                <p>Total: {$total} | Admitted: {$admitted}</p>
                <h3>Students:</h3><ul>"
                . $students->map(fn($s) => "<li>{$s->first_name} {$s->last_name} - {$s->email}</li>")->implode('')
                . "</ul>
                <p><a href='/admin/dashboard?real=1'>Try real Blade</a></p>
                </body></html>
            ", 200);
        } catch (Throwable $e) {
            return response("CRASH: ".$e->getMessage()." at ".$e->getFile().":".$e->getLine()."<br><pre>".$e->getTraceAsString()."</pre>", 500);
        }
    }

    // keep your other methods below - don't delete them
    public function show(\App\Models\Student $student): \Illuminate\View\View
    {
        $exams = \App\Models\Exam::orderByDesc('created_at')->get(['id', 'title']);
        $examSubmission = $student->examSubmission;
        return view('admin.view_student', compact('student', 'exams', 'examSubmission'));
    }
    public function setExamDate(\Illuminate\Http\Request $request, \App\Models\Student $student): \Illuminate\Http\RedirectResponse { $data = $request->validate(['exam_date'=>'required|date','exam_id'=>'nullable|exists:exams,id']); $student->update(['exam_date'=>$data['exam_date'],'exam_id'=>$data['exam_id'] ?? null]); return redirect()->route('admin.students.show',$student); }
    public function markExamCompleted(\App\Models\Student $student): \Illuminate\Http\RedirectResponse { $student->update(['exam_completed'=>true]); return redirect()->route('admin.students.show',$student); }
    public function verify(\App\Models\Student $student): \Illuminate\Http\RedirectResponse { $student->update(['status'=>'active','admission_status'=>'admitted']); return redirect()->route('admin.students.show',$student); }
    public function decline(\App\Models\Student $student): \Illuminate\Http\RedirectResponse { $student->update(['status'=>'declined','admission_status'=>'undecided']); return redirect()->route('admin.students.show',$student); }
    public function destroy(\App\Models\Student $student): \Illuminate\Http\RedirectResponse { $student->delete(); return redirect()->route('admin.dashboard')->with('status','Student deleted.'); }
}
