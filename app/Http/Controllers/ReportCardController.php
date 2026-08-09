<?php

namespace App\Http\Controllers;

use App\Models\ReportCard;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReportCardController extends Controller
{
    // Replaces the $page == 'teacher_report_cards' branch: upload form
    // (per student + term) plus a list of report cards already uploaded
    // for the teacher's class.
    public function teacherIndex(): View
    {
        $teacher = Auth::guard('teacher')->user();
        $teacherClass = $teacher->assigned_class;

        $students = $teacherClass
            ? Student::where('class', $teacherClass)->where('status', 'active')
                ->orderBy('last_name')->orderBy('first_name')->get()
            : collect();

        $reportCards = $teacherClass
            ? ReportCard::whereIn('student_id', $students->pluck('id'))
                ->with('student')
                ->join('students', 'report_cards.student_id', '=', 'students.id')
                ->orderBy('students.last_name')
                ->orderBy('students.first_name')
                ->orderBy('report_cards.term')
                ->select('report_cards.*')
                ->get()
            : collect();

        return view('teacher.report-cards', [
            'teacher' => $teacher,
            'students' => $students,
            'reportCards' => $reportCards,
            'noClassAssigned' => !$teacherClass,
        ]);
    }

    // Replaces the upload_report_card POST handler. Uses updateOrCreate to
    // match the original's `INSERT ... ON DUPLICATE KEY UPDATE` on
    // (student_id, term): re-uploading for the same student+term replaces
    // the file rather than creating a duplicate row.
    public function store(Request $request): RedirectResponse
    {
        $teacher = Auth::guard('teacher')->user();

        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'term' => 'required|string|max:20',
            'report_file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        $student = Student::findOrFail($data['student_id']);
        if ($student->class !== $teacher->assigned_class) {
            abort(403);
        }

        $existing = ReportCard::where('student_id', $data['student_id'])
            ->where('term', $data['term'])
            ->first();

        if ($existing && $existing->file_path) {
            Storage::disk('public')->delete($existing->file_path);
        }

        $path = $request->file('report_file')->store('uploads/report_cards', 'public');

        ReportCard::updateOrCreate(
            ['student_id' => $data['student_id'], 'term' => $data['term']],
            ['teacher_id' => $teacher->id, 'file_path' => $path]
        );

        return redirect()->route('teacher.report-cards')->with('status', 'Report card uploaded successfully.');
    }

    // Replaces the delete_report_card GET handler: ownership check plus
    // removing the stored file.
    public function destroy(ReportCard $reportCard): RedirectResponse
    {
        if ($reportCard->teacher_id !== Auth::guard('teacher')->id()) {
            abort(403);
        }

        if ($reportCard->file_path) {
            Storage::disk('public')->delete($reportCard->file_path);
        }

        $reportCard->delete();

        return redirect()->route('teacher.report-cards')->with('status', 'Report card deleted.');
    }

    // Replaces the $page == 'student_report_card' branch: read-only list of
    // the student's own report cards, newest term first.
    public function studentIndex(): View
    {
        $student = Auth::guard('student')->user();

        $reportCards = ReportCard::where('student_id', $student->id)
            ->orderByDesc('term')
            ->orderByDesc('uploaded_at')
            ->get();

        return view('student.report-card', [
            'student' => $student,
            'reportCards' => $reportCards,
        ]);
    }
}
