<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class HomeController extends Controller
{
    /**
     * Central Platform Marketing Page (Root URL: /)
     * Pure platform landing page - bypasses view crashes for diagnostics.
     */
    public function centralHome()
    {
        // --- DIAGNOSTIC BYPASS START ---
        if (request()->has('bypass') || !view()->exists('platform.home')) {
            return response()->json([
                'status' => 'centralHome reached',
                'view_platform_home_exists' => view()->exists('platform.home'),
                'app_env' => config('app.env'),
                'app_debug' => config('app.debug'),
            ]);
        }
        // --- DIAGNOSTIC BYPASS END ---

        return view('platform.home');
    }

    /**
     * Tenant School Homepage (/school-home)
     * Safely checks school resolution and view existence without throwing a 500 error.
     */
    public function index(Request $request)
    {
        $diagnostics = [
            'status' => 'index reached',
            'shared_school' => false,
            'session_school_id' => session('active_school_id', null),
            'db_connection' => false,
            'db_error' => null,
            'views' => [
                'platform.home' => view()->exists('platform.home'),
                'home' => view()->exists('home'),
                'home-generic' => view()->exists('home-generic'),
            ]
        ];

        // 1. Check DB Connection safely
        try {
            DB::connection()->getPdo();
            $diagnostics['db_connection'] = true;
        } catch (Throwable $e) {
            $diagnostics['db_error'] = $e->getMessage();
        }

        // --- DIRECT BYPASS TRIGGER (?bypass=1) ---
        if ($request->has('bypass')) {
            return response()->json($diagnostics);
        }

        // 2. Safe Tenant Resolution
        $school = view()->shared('currentSchool');
        if ($school) {
            $diagnostics['shared_school'] = true;
        }

        if (!$school && session()->has('active_school_id') && $diagnostics['db_connection']) {
            try {
                $school = School::find(session('active_school_id'));
            } catch (Throwable $e) {
                $diagnostics['db_error'] = 'School query failed: ' . $e->getMessage();
            }
        }

        if (!$school) {
            if ($diagnostics['views']['platform.home']) {
                return view('platform.home');
            }
            return response()->json([
                'error' => 'No school resolved and view [platform.home] is missing.',
                'diagnostics' => $diagnostics
            ], 500);
        }

        // 3. Safe View Rendering
        $identifier = $school->subdomain ?? $school->slug ?? '';

        if ($identifier === 'royalcountrysideacademy' && $diagnostics['views']['home']) {
            return view('home', compact('school'));
        }

        if ($diagnostics['views']['home-generic']) {
            return view('home-generic', compact('school'));
        }

        if ($diagnostics['views']['platform.home']) {
            return view('platform.home');
        }

        // If all expected view templates are missing from disk
        return response()->json([
            'error' => 'No valid Blade views found for rendering.',
            'diagnostics' => $diagnostics
        ], 500);
    }
}
