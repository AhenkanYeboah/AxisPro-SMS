<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SchoolSettingsController extends Controller
{
    // How a school makes their instance look like theirs: logo, accent
    // color, homepage tagline, and public contact details. Every school
    // sees these fields reflected in their dashboard sidebar and (unless
    // they're RCA - see HomeController) their public homepage too.
    public function show(): View
    {
        return view('admin.settings', [
            'school' => app('currentSchool'),
            'curricula' => Curriculum::where('is_active', true)->orderBy('name')->get(),
            'activatedCurriculumIds' => app('currentSchool')->curricula()->pluck('curricula.id')->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $school = app('currentSchool');

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'tagline' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
            'contact_email' => 'nullable|email|max:150',
            'primary_color' => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'logo' => 'nullable|file|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            if ($school->logo_path) {
                Storage::disk('public')->delete($school->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('branding', 'public');
        }

        unset($data['logo']);
        $school->update($data);

        return redirect()->route('admin.settings')->with('status', 'Branding updated.');
    }

    // Deactivating a curriculum here does NOT touch existing class_levels
    // that reference it - a school shouldn't lose its class/curriculum
    // assignments just because it unchecked a box; it only stops that
    // curriculum from appearing as an option when creating/editing a
    // class going forward (see AdminClassLevelController::assertCurriculumActivated).
    public function updateCurricula(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'curricula' => 'required|array|min:1',
            'curricula.*' => 'integer|exists:curricula,id',
        ]);

        app('currentSchool')->curricula()->sync($data['curricula']);

        return redirect()->route('admin.settings')->with('status', 'Curricula updated.');
    }
}
