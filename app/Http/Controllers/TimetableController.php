<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TimetableController extends Controller
{
    // Replaces the $page == 'teacher_timetable' branch: upload form + list,
    // scoped to the teacher's own assigned_class.
    public function teacherIndex(): View
    {
        $teacher = Auth::guard('teacher')->user();
        $teacherClass = $teacher->assigned_class;

        $timetables = $teacherClass
            ? Timetable::where('class', $teacherClass)->orderByDesc('uploaded_at')->get()
            : collect();

        return view('teacher.timetable', [
            'teacher' => $teacher,
            'timetables' => $timetables,
            'noClassAssigned' => !$teacherClass,
        ]);
    }

    // Replaces the upload_timetable POST handler.
    public function store(Request $request): RedirectResponse
    {
        $teacher = Auth::guard('teacher')->user();

        if (!$teacher->assigned_class) {
            return redirect()->route('teacher.timetable')
                ->with('error', "You don't have a class assigned.");
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'timetable_file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,xls,xlsx|max:5120',
        ]);

        $path = $request->file('timetable_file')->store('uploads/timetables', 'public');

        Timetable::create([
            'class' => $teacher->assigned_class,
            'teacher_id' => $teacher->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_path' => $path,
        ]);

        return redirect()->route('teacher.timetable')->with('status', 'Timetable uploaded successfully.');
    }

    // Replaces the delete_timetable GET handler. Ownership check (teacher_id
    // must match) plus removing the stored file, same as the original's
    // file_exists()/unlink() pair.
    public function destroy(Timetable $timetable): RedirectResponse
    {
        if ($timetable->teacher_id !== Auth::guard('teacher')->id()) {
            abort(403);
        }

        if ($timetable->file_path) {
            Storage::disk('public')->delete($timetable->file_path);
        }

        $timetable->delete();

        return redirect()->route('teacher.timetable')->with('status', 'Timetable deleted.');
    }

    // Replaces the $page == 'student_timetable' branch: read-only list
    // scoped to the student's own class.
    public function studentIndex(): View
    {
        $student = Auth::guard('student')->user();

        $timetables = $student->class
            ? Timetable::where('class', $student->class)->orderByDesc('uploaded_at')->get()
            : collect();

        return view('student.timetable', [
            'student' => $student,
            'timetables' => $timetables,
        ]);
    }
}
