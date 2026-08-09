<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    // Same 3 fixed term start dates as the original file.
    private const TERM_START_DATES = [
        1 => '2024-09-02', // first Monday of September 2024
        2 => '2025-01-06', // first Monday of January 2025
        3 => '2025-04-07', // first Monday of April 2025
    ];

    public function index(Request $request): View
    {
        $currentTerm = (int) $request->query('term', 1);
        if ($currentTerm < 1 || $currentTerm > 3) {
            $currentTerm = 1;
        }

        $teacher = Auth::guard('teacher')->user();
        $teacherClass = $teacher->assigned_class;

        $message = null;
        $messageType = null;
        if (!$teacherClass) {
            $message = 'You are not assigned to any class. Please contact the admin.';
            $messageType = 'error';
        }

        // Build the list of weekday dates for the term - 16 weeks, Mon-Fri only.
        // Same generation logic as the original: walk forward day by day,
        // skip Saturday/Sunday (ISO weekday 6 or 7), until 80 weekdays collected.
        $termStart = Carbon::parse(self::TERM_START_DATES[$currentTerm]);
        $dates = [];
        $cursor = $termStart->copy();
        $weekdaysNeeded = 16 * 5;
        while (count($dates) < $weekdaysNeeded) {
            if ($cursor->dayOfWeekIso < 6) {
                $dates[] = $cursor->copy();
            }
            $cursor->addDay();
        }

        $students = $teacherClass
            ? Student::where('class', $teacherClass)
                ->where('status', 'active')
                ->orderByDesc('gender')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
            : collect();

        $male = $students->where('gender', 'Male');
        $female = $students->where('gender', 'Female');

        $existingAttendance = [];
        if ($students->isNotEmpty()) {
            $rows = Attendance::whereIn('student_id', $students->pluck('id'))
                ->whereIn('date', array_map(fn ($d) => $d->format('Y-m-d'), $dates))
                ->where('term', $currentTerm)
                ->get();

            foreach ($rows as $row) {
                $existingAttendance[$row->student_id][$row->date->format('Y-m-d')] = $row->status;
            }
        }

        $showSummary = $request->query('summary') === '1';
        $summaryData = [];
        if ($showSummary && $students->isNotEmpty()) {
            $rows = Attendance::whereIn('student_id', $students->pluck('id'))
                ->selectRaw('student_id, term, status, count(*) as count')
                ->groupBy('student_id', 'term', 'status')
                ->get();

            foreach ($rows as $row) {
                $summaryData[$row->student_id][$row->term][$row->status] = $row->count;
            }
        }

        return view('teacher.attendance', [
            'currentTerm' => $currentTerm,
            'dates' => $dates,
            'male' => $male,
            'female' => $female,
            'students' => $students,
            'existingAttendance' => $existingAttendance,
            'showSummary' => $showSummary,
            'summaryData' => $summaryData,
            'message' => $message,
            'messageType' => $messageType,
        ]);
    }

    // Replaces the ON DUPLICATE KEY UPDATE upsert loop. Eloquent's updateOrCreate
    // does the same thing: try to find a row matching student_id+date, update its
    // status if found, otherwise create it - one call per cell, same as the original.
    public function save(Request $request): RedirectResponse
    {
        $term = (int) $request->input('term', 1);
        $attendance = $request->input('attendance', []);

        $teacher = Auth::guard('teacher')->user();
        $teacherClass = $teacher->assigned_class;

        // Only allow writes for students who actually belong to this teacher's
        // class - the original PHP trusted $_POST['attendance'] student IDs
        // outright, which let a crafted request mark attendance for any
        // student. Scope it down here instead.
        $allowedStudentIds = $teacherClass
            ? Student::where('class', $teacherClass)->pluck('id')->flip()
            : collect();

        foreach ($attendance as $studentId => $datesStatus) {
            if (!$allowedStudentIds->has((int) $studentId)) {
                continue;
            }

            foreach ($datesStatus as $dateStr => $status) {
                if (!in_array($status, ['present', 'absent', 'holiday'], true)) {
                    continue;
                }

                Attendance::updateOrCreate(
                    ['student_id' => $studentId, 'date' => $dateStr, 'term' => $term],
                    ['status' => $status]
                );
            }
        }

        return redirect()->route('teacher.attendance', ['term' => $term])
            ->with('status', "Attendance for Term {$term} saved successfully!");
    }
}
