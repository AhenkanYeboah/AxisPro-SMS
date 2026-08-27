<?php

namespace App\Http\Controllers;

use App\Models\LibraryMaterial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentLibraryController extends Controller
{
    // A student sees: material tagged to their own class_level_id, PLUS
    // material with no class_level_id at all (school-wide/general library),
    // filtered further by subject when subject_id is set. Never another
    // class's material.
    public function index(): View
    {
        $student = Auth::guard('student')->user();

        $materials = LibraryMaterial::where('school_id', $student->school_id)
            ->where(function ($query) use ($student) {
                $query->whereNull('class_level_id')
                    ->orWhere('class_level_id', $student->class_level_id);
            })
            ->with(['subject', 'classLevel'])
            ->latest()
            ->get();

        return view('student.library', [
            'student' => $student,
            'materials' => $materials,
            'subjects' => $materials->pluck('subject')->filter()->unique('id')->sortBy('name')->values(),
        ]);
    }

    public function read(LibraryMaterial $material): View
    {
        $this->authorizeVisible($material);

        return view('student.library-read', [
            'material' => $material,
        ]);
    }

    // Serves the raw file inline (Content-Disposition: inline) for the
    // in-browser reader's <iframe> - available regardless of
    // allow_download, since "view-only" only ever means "no download",
    // never "no reading".
    public function stream(LibraryMaterial $material): StreamedResponse
    {
        $this->authorizeVisible($material);

        return Storage::disk('local')->response($material->file_path, null, [
            'Content-Disposition' => 'inline; filename="' . $material->title . '.' . $material->file_type . '"',
        ]);
    }

    public function download(LibraryMaterial $material): StreamedResponse
    {
        $this->authorizeVisible($material);

        abort_unless($material->allow_download, 403, 'This material is view-only.');

        return Storage::disk('local')->download($material->file_path, $material->title . '.' . $material->file_type);
    }

    private function authorizeVisible(LibraryMaterial $material): void
    {
        $student = Auth::guard('student')->user();

        $visible = $material->school_id === $student->school_id
            && ($material->class_level_id === null || $material->class_level_id === $student->class_level_id);

        abort_unless($visible, 403);
    }
}
