<?php

namespace App\Http\Controllers;

use App\Models\LibraryMaterial;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminLibraryController extends Controller
{
    public function index(): View
    {
        $school = app('currentSchool');

        return view('admin.library', [
            'materials' => LibraryMaterial::where('school_id', $school->id)
                ->with(['subject', 'classLevel'])
                ->latest()
                ->get(),
            'classLevels' => $school->classLevels()->orderBy('sort_order')->get(),
            'subjects' => Subject::whereIn('curriculum_id', $school->curricula()->pluck('curricula.id'))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $school = app('currentSchool');
        $admin = Auth::guard('admin')->user();

        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|max:40',
            'class_level_id' => 'nullable|exists:class_levels,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'allow_download' => 'nullable|boolean',
            'material_file' => 'required|file|mimes:pdf,doc,docx,ppt,pptx,epub,txt|max:20480',
        ]);

        if ($data['class_level_id'] ?? null) {
            abort_unless(
                $school->classLevels()->where('id', $data['class_level_id'])->exists(),
                403,
                'That class does not belong to your school.'
            );
        }

        // Stored on the private 'local' disk, not 'public' - library
        // materials are only ever reachable through the authenticated
        // student/admin/teacher controllers below, never by a raw public
        // URL. That's what actually makes allow_download=false meaningful:
        // a view-only material has no direct link to leak in the first
        // place, rather than just a hidden download button.
        $file = $request->file('material_file');
        $path = $file->store('library', 'local');

        LibraryMaterial::create([
            'school_id' => $school->id,
            'subject_id' => $data['subject_id'] ?? null,
            'class_level_id' => $data['class_level_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'],
            'file_path' => $path,
            'file_type' => strtolower($file->getClientOriginalExtension()),
            'file_size' => $file->getSize(),
            'allow_download' => $request->boolean('allow_download', true),
            'uploaded_by_type' => 'admin',
            'uploaded_by_admin_id' => $admin->id,
        ]);

        return redirect()->route('admin.library.index')->with('success', 'Material added to the library.');
    }

    public function destroy(LibraryMaterial $material): RedirectResponse
    {
        abort_unless($material->school_id === app('currentSchool')->id, 403);

        Storage::disk('local')->delete($material->file_path);
        $material->delete();

        return back()->with('success', 'Material removed from the library.');
    }
}
