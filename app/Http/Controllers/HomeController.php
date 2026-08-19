<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Render central marketing homepage for root application URL.
     */
    public function centralHome()
    {
        return view('home');
    }

    /**
     * Render resolved school public homepage.
     */
    public function index(Request $request)
    {
        $school = view()->shared('currentSchool');

        if (!$school && session()->has('active_school_id')) {
            $school = School::find(session('active_school_id'));
        }

        // Fallback: If no school resolved, load the default first school record
        if (!$school) {
            $school = School::first();
        }

        if (!$school) {
            return redirect()->route('home');
        }

        // Return 'home' because home.blade.php is in resources/views/
        return view('home', compact('school'));
    }
}
