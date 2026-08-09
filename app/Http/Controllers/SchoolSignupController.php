<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Curriculum;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SchoolSignupController extends Controller
{
    // This is the ONE central, tenant-agnostic entry point in the whole
    // app: everywhere else assumes a school has already been resolved from
    // the subdomain (see App\Http\Middleware\ResolveTenant). This page is
    // how a school comes to exist in the first place.
    public function create(): View
    {
        return view('school.signup', [
            'curricula' => Curriculum::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $reserved = config('saas.reserved_subdomains');

        $data = $request->validate([
            'school_name' => 'required|string|max:150',
            'subdomain' => [
                'required', 'string', 'max:60', 'min:3',
                'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', // lowercase letters/numbers/hyphens only
                'unique:schools,subdomain',
                function ($attribute, $value, $fail) use ($reserved) {
                    if (in_array($value, $reserved, true)) {
                        $fail('That subdomain is reserved. Please choose another.');
                    }
                },
            ],
            'admin_name' => 'required|string|max:150',
            'admin_email' => 'required|email|max:150',
            'admin_username' => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9_.]+$/',
            'admin_password' => 'required|string|min:8|confirmed',
            // At least one curriculum required - everything downstream
            // (class levels, subjects, the AI research assistant's
            // grounding) hangs off a school having at least one curriculum
            // activated from day one, so this can't be left optional/empty.
            'curricula' => 'required|array|min:1',
            'curricula.*' => 'integer|exists:curricula,id',
        ]);

        $school = School::create([
            'name' => $data['school_name'],
            'subdomain' => strtolower($data['subdomain']),
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(config('saas.trial_days')),
        ]);

        $school->curricula()->attach($data['curricula']);

        $this->seedDefaultClassLevels($school, $data['curricula']);

        // Explicitly pass school_id here - don't rely on the model's
        // auto-fill-from-currentSchool hook (BelongsToSchool), since the
        // "current school" for THIS request is whatever the subdomain
        // resolved to (or the dev fallback), which is almost certainly NOT
        // the brand new school being created right now.
        Admin::create([
            'school_id' => $school->id,
            'admin_id' => 'A'.strtoupper(Str::random(9)),
            'username' => $data['admin_username'],
            'email' => $data['admin_email'],
            'full_name' => $data['admin_name'],
            'password' => $data['admin_password'], // hashed automatically via the model's 'hashed' cast
            'role' => 'admin',
        ]);

        return redirect()->route('school.signup.success', $school);
    }

    public function success(School $school): View
    {
        return view('school.signup-success', [
            'school' => $school,
            'loginUrl' => 'https://'.$school->subdomain.'.'.config('saas.base_domain').'/admin/login',
        ]);
    }

    // Without this, a school signs up, picks a curriculum, and lands with
    // zero classes - every one has to be hand-created afterward on the new
    // Classes screen before anything (students, the research assistant)
    // is usable. Seeding sensible defaults per curriculum removes that
    // dead-end first-run experience; the admin can still rename, reorder,
    // add sections, or delete any of these afterward.
    private function seedDefaultClassLevels(School $school, array $curriculumIds): void
    {
        // Uses RCA's own real-world naming ("Primary"/"JHS") rather than
        // NaCCA's formal "Basic 1-9" numbering, since that's what we
        // confirmed is actually in use in the live data (see the
        // backfill migration's findings) - closer to what a newly
        // signed-up Ghanaian school will expect to see out of the box.
        $defaultsByCurriculumCode = [
            'GES' => [
                'KG 1', 'KG 2',
                'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6',
                'JHS 1', 'JHS 2', 'JHS 3',
            ],
            'CAMBRIDGE' => [
                'Year 1', 'Year 2', 'Year 3', 'Year 4', 'Year 5',
                'Year 6', 'Year 7', 'Year 8', 'Year 9',
            ],
        ];

        $curricula = Curriculum::whereIn('id', $curriculumIds)->get();

        foreach ($curricula as $curriculum) {
            $classNames = $defaultsByCurriculumCode[$curriculum->code] ?? null;

            // Unrecognised curriculum code (future curricula added without
            // a default list here) - skip seeding rather than guess, the
            // admin creates classes manually via the Classes screen.
            if (! $classNames) {
                continue;
            }

            foreach ($classNames as $index => $name) {
                \App\Models\ClassLevel::create([
                    'school_id' => $school->id, // explicit - see note above on Admin::create()
                    'curriculum_id' => $curriculum->id,
                    'name' => $name,
                    'sort_order' => $index,
                ]);
            }
        }
    }
}
