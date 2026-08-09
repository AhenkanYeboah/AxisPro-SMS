<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminExamController extends Controller
{
    // Exam bank: admin builds entrance exams here (typed questions and/or an
    // uploaded PDF/Word paper), then assigns one to each applicant from the
    // "Set Exam Date" action on their profile page.
    public function index(): View
    {
        $exams = Exam::withCount(['assignedStudents', 'submissions'])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.exams.index', compact('exams'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'instructions' => 'nullable|string',
            'questions' => 'nullable|array',
            'questions.*' => 'nullable|string|max:2000',
            'exam_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        // Drop any blank rows from the dynamic question builder.
        $questions = collect($data['questions'] ?? [])
            ->map(fn ($q) => trim((string) $q))
            ->filter()
            ->values()
            ->all();

        $filePath = null;
        $fileOriginalName = null;
        if ($request->hasFile('exam_file')) {
            $filePath = $request->file('exam_file')->store('uploads/exams', 'public');
            $fileOriginalName = $request->file('exam_file')->getClientOriginalName();
        }

        if (empty($questions) && !$filePath) {
            return back()->withErrors(['questions' => 'Add at least one typed question or upload a question paper.'])->withInput();
        }

        Exam::create([
            'title' => $data['title'],
            'instructions' => $data['instructions'] ?? null,
            'questions' => $questions ?: null,
            'file_path' => $filePath,
            'file_original_name' => $fileOriginalName,
            'created_by_admin_id' => Auth::guard('admin')->id(),
        ]);

        return redirect()->route('admin.exams.index')->with('status', 'Exam created. You can now assign it to applicants from their profile page.');
    }

    public function show(Exam $exam): View
    {
        $submissions = $exam->submissions()->with('student')->orderByDesc('submitted_at')->get();

        return view('admin.exams.show', compact('exam', 'submissions'));
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        if ($exam->file_path) {
            Storage::disk('public')->delete($exam->file_path);
        }

        $exam->delete(); // students.exam_id is set nullOnDelete(), submissions cascade

        return redirect()->route('admin.exams.index')->with('status', 'Exam deleted.');
    }

    // Admin reviews an applicant's answers and records a score/feedback.
    public function grade(Request $request, ExamSubmission $submission): RedirectResponse
    {
        $data = $request->validate([
            'score' => 'nullable|numeric|min:0|max:100',
            'feedback' => 'nullable|string|max:2000',
        ]);

        $submission->update($data);

        return back()->with('status', 'Submission graded.');
    }
}
