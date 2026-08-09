<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentExamController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $student = Auth::guard('student')->user();

        if (!$student->exam_id) {
            return redirect()->route('student.dashboard')->with('error', 'No exam has been scheduled for you yet.');
        }

        $exam = $student->exam;
        $submission = $student->examSubmission;

        return view('student.exam', compact('student', 'exam', 'submission'));
    }

    public function submit(Request $request): RedirectResponse
    {
        $student = Auth::guard('student')->user();

        if (!$student->exam_id) {
            return redirect()->route('student.dashboard')->with('error', 'No exam has been scheduled for you.');
        }

        if ($student->examSubmission) {
            return redirect()->route('student.exam')->with('error', 'You have already submitted this exam.');
        }

        $data = $request->validate([
            'answers' => 'nullable|array',
            'answers.*' => 'nullable|string|max:5000',
            'answer_text' => 'nullable|string|max:10000',
            'answer_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $answerFilePath = null;
        if ($request->hasFile('answer_file')) {
            $answerFilePath = $request->file('answer_file')->store('uploads/exam_answers', 'public');
        }

        $student->examSubmission()->create([
            'exam_id' => $student->exam_id,
            'answers' => $data['answers'] ?? null,
            'answer_text' => $data['answer_text'] ?? null,
            'answer_file' => $answerFilePath,
            'submitted_at' => now(),
        ]);

        // Submitting the exam is what "completes" it - replaces the admin's
        // old manual "Mark Exam Done" button for the online-exam flow.
        $student->update(['exam_completed' => true]);

        return redirect()->route('student.dashboard')->with('status', 'Exam submitted successfully. The admin will review your answers.');
    }
}
