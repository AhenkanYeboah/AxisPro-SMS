<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class HomeController extends Controller
{
    /**
     * Admin Dashboard Route (/admin/dashboard)
     */
    public function adminDashboard()
    {
        // 1. Resolve school from container, session, or authenticated admin
        $school = app()->bound('currentSchool') ? app('currentSchool') : null;

        if (!$school && session()->has('active_school_id')) {
            $school = School::find(session('active_school_id'));
        }

        if (!$school && auth('admin')->check()) {
            $school = auth('admin')->user()->school;
        }

        // 2. Fallback check to prevent 500 crashes
        if (!$school) {
            return redirect()->route('platform.login')->with('error', 'No active school context found.');
        }

        // 3. Render dashboard view with fallback view checks
        if (view()->exists('admin.dashboard')) {
            return view('admin.dashboard', compact('school'));
        }

        if (view()->exists('dashboard')) {
            return view('dashboard', compact('school'));
        }

        return response()->json([
            'status' => 'Admin Dashboard Reached',
            'school' => $school->name ?? 'Unknown School',
            'admin' => auth('admin')->user()->email ?? 'Logged In',
        ]);
    }

    /**
     * Central Platform Marketing Page (Root URL: /)
     */
    public function centralHome()
    {
        if (request()->has('bypass') || !view()->exists('platform.home')) {
            return response()->json([
                'status' => 'centralHome reached',
                'view_platform_home_exists' => view()->exists('platform.home'),
                'app_env' => config('app.env'),
                'app_debug' => config('app.debug'),
            ]);
        }

        return view('platform.home');
    }

    /**
     * Tenant School Homepage (/school-home)
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

        try {
            DB::connection()->getPdo();
            $diagnostics['db_connection'] = true;
        } catch (Throwable $e) {
            $diagnostics['db_error'] = $e->getMessage();
        }

        if ($request->has('bypass')) {
            return response()->json($diagnostics);
        }

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

        if (!$school && auth('admin')->check()) {
            $school = auth('admin')->user()->school;
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

        return response()->json([
            'error' => 'No valid Blade views found for rendering.',
            'diagnostics' => $diagnostics
        ], 500);
    }
}
