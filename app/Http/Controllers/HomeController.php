<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Platform Central Marketing Page (Root URL: /)
     * Renders your main platform landing page using resources/views/home.blade.php.
     */
    public function centralHome()
    {
        return view('home');
    }

    /**
     * School Tenant Homepage (/school-home)
     * Renders the tenant-specific school page.
     */
    public function index(Request $request)
    {
        $school = view()->shared('currentSchool');

        if (!$school && session()->has('active_school_id')) {
            $school = School::find(session('active_school_id'));
        }

        if (!$school) {
            $school = School::first();
        }

        if (!$school) {
            return redirect()->route('home');
        }

        // Checks for dedicated school views first, fallback to home-generic or home
        if (view()->exists('school.home')) {
            return view('school.home', compact('school'));
        }

        if (view()->exists('home-generic')) {
            return view('home-generic', compact('school'));
        }

        return view('home', compact('school'));
    }
}
