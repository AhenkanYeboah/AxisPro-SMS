<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Render central marketing homepage for root hits.
     */
    public function centralHome()
    {
        return view('welcome');
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

        if (!$school) {
            return redirect()->route('home');
        }

        return view('school.home', compact('school'));
    }
}
