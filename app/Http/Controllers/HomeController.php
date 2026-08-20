<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Central Platform Marketing Page (Root URL: /)
     * Pure platform landing page - no school data passed or required.
     */
    public function centralHome()
    {
        return view('platform.home');
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
            // Render the platform view directly instead of sending an HTTP redirect
            return view('platform.home');
        }

        // Royal Countryside Academy keeps its own bespoke branded template.
        // Every other tenant gets the shared generic per-school template.
        $identifier = $school->subdomain ?? $school->slug ?? '';

        if ($identifier === 'royalcountrysideacademy' && view()->exists('home')) {
            return view('home', compact('school'));
        }

        if (view()->exists('home-generic')) {
            return view('home-generic', compact('school'));
        }

        return view('platform.home');
    }
}
