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
    // Central tenant-agnostic entry point for school registration
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
            'curricula' => 'required|array|min:1',
            'curricula.*' => 'integer|exists:curricula,id',
        ]);

        // Auto-generate slug from school name for path-based multi-tenancy
        $school = School::create([
            'name' => $data['school_name'],
            'slug' => Str::slug($data['school_name']),
            'subdomain' => strtolower($data['subdomain']),
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(config('saas.trial_days')),
        ]);

        $school->curricula()->attach($data['curricula']);

        $this->seedDefaultClassLevels($school, $data['curricula']);

        // Explicitly pass school_id for the new tenant
        Admin::create([
            'school_id' => $school->id,
            'admin_id' => 'A'.strtoupper(Str::random(9)),
            'username' => $data['admin_username'],
            'email' => $data['admin_email'],
            'full_name' => $data['admin_name'],
            'password' => $data['admin_password'], // hashed via model 'hashed' cast
            'role' => 'admin',
        ]);

        return redirect()->route('school.signup.success', $school);
    }

    public function success(School $school): View
    {
        // Path-based login URL compatible with Render Free tier
        return view('school.signup-success', [
            'school' => $school,
            'loginUrl' => url('/school/' . $school->slug . '/admin/login'),
        ]);
    }

    private function seedDefaultClassLevels(School $school, array $curriculumIds): void
    {
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

            if (! $classNames) {
                continue;
            }

            foreach ($classNames as $index => $name) {
                \App\Models\ClassLevel::create([
                    'school_id' => $school->id,
                    'curriculum_id' => $curriculum->id,
                    'name' => $name,
                    'sort_order' => $index,
                ]);
            }
        }
    }
}