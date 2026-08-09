<?php

namespace App\Http\Controllers;

use App\Models\SchoolActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    // Replaces the sequential if/if/if notification-building block. Same logic,
    // expressed as an array we build up and hand to the view.
    public function index(): View
    {
        $student = Auth::guard('student')->user();
        $notifications = [];

        if ($student->exam_date) {
            $canTakeExam = $student->exam_id && !$student->exam_completed;

            $notifications[] = [
                'date' => $student->exam_date,
                'title' => '📅 Entrance Exam Scheduled',
                'desc' => $canTakeExam
                    ? 'Your entrance exam is scheduled for '.$student->exam_date->format('F j, Y').'. It\'s ready for you to take online below.'
                    : 'Your entrance exam is scheduled for '.$student->exam_date->format('F j, Y').'. Please come prepared.',
                'action_url' => $canTakeExam ? route('student.exam') : null,
                'action_label' => $canTakeExam ? 'Take Exam →' : null,
            ];
        }

        if ($student->exam_completed && !$student->exam_verified) {
            $notifications[] = [
                'date' => now(),
                'title' => '✅ Exam Completed',
                'desc' => 'You have completed your entrance exam. Awaiting verification from the admin.',
            ];
        }

        if ($student->exam_verified) {
            $notifications[] = [
                'date' => now(),
                'title' => '🎉 Exam Verified!',
                'desc' => 'Your exam has been verified. You are now a verified student!',
            ];
        }

        if ($student->status === 'active') {
            $notifications[] = [
                'date' => now(),
                'title' => "🏫 Welcome to {$student->school->name}!",
                'desc' => 'You are now officially a student. Check out the school activities below.',
            ];
        }

        if (empty($notifications)) {
            $notifications[] = [
                'date' => now(),
                'title' => '👋 Welcome!',
                'desc' => 'Your enrollment is pending. Check back for updates on your exam schedule.',
            ];
        }

        $activities = SchoolActivity::orderBy('activity_date')->get();

        return view('student.dashboard', compact('student', 'notifications', 'activities'));
    }
}
