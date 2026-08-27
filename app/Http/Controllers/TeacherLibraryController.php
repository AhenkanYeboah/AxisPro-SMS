<?php

namespace App\Http\Controllers;

use App\Models\LibraryMaterial;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TeacherLibraryController extends Controller
{
    public function index(): View
    {
        $teacher = Auth::guard('teacher')->user();
        $school = app('currentSchool');

        $subjects = $teacher->classLevel
            ? Subject::where('curriculum_id', $teacher->classLevel->curriculum_id)->orderBy('name')->get()
            : collect();

        return view('teacher.library', [
            'teacher' => $teacher,
            'materials' => LibraryMaterial::where('school_id', $school->id)
                ->where('uploaded_by_type', 'teacher')
                ->where('uploaded_by_teacher_id', $teacher->id)
                ->with(['subject', 'classLevel'])
                ->latest()
                ->get(),
            'subjects' => $subjects,
            'noClassAssigned' => !$teacher->classLevel,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $teacher = Auth::guard('teacher')->user();

        if (!$teacher->classLevel) {
            return redirect()->back()->with('error', "You don't have a class assigned.");
        }

        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|max:40',
            'subject_id' => 'nullable|exists:subjects,id',
            'allow_download' => 'nullable|boolean',
            'material_file' => 'required|file|mimes:pdf,doc,docx,ppt,pptx,epub,txt|max:20480',
        ]);

        // A teacher's material is always scoped to their own class - unlike
        // admins, who can target any class in the school, a teacher only
        // ever uploads for the class they're assigned to. Stored on the
        // private 'local' disk - see AdminLibraryController::store() for why.
        $file = $request->file('material_file');
        $path = $file->store('library', 'local');

        LibraryMaterial::create([
            'school_id' => $teacher->school_id,
            'subject_id' => $data['subject_id'] ?? null,
            'class_level_id' => $teacher->class_level_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'],
            'file_path' => $path,
            'file_type' => strtolower($file->getClientOriginalExtension()),
            'file_size' => $file->getSize(),
            'allow_download' => $request->boolean('allow_download', true),
            'uploaded_by_type' => 'teacher',
            'uploaded_by_teacher_id' => $teacher->id,
        ]);

        return redirect()->route('teacher.library')->with('status', 'Material added to the library.');
    }

    public function destroy(LibraryMaterial $material): RedirectResponse
    {
        $teacher = Auth::guard('teacher')->user();

        abort_unless(
            $material->uploaded_by_type === 'teacher' && $material->uploaded_by_teacher_id === $teacher->id,
            403
        );

        Storage::disk('local')->delete($material->file_path);
        $material->delete();

        return back()->with('status', 'Material removed.');
    }
}
