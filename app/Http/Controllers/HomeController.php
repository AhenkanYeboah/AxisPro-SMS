<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Central Platform Marketing Page (Root URL: /)
     * Pure platform landing page—no school data passed or required.
     */
    public function centralHome()
    {
        return view('home');
    }

    /**
     * Tenant School Homepage (/school-home)
     * Renders tenant view with school context.
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

        // Render dedicated school template if available
        if (view()->exists('school.home')) {
            return view('school.home', compact('school'));
        }

        if (view()->exists('home-generic')) {
            return view('home-generic', compact('school'));
        }

        return view('home', compact('school'));
    }
}
