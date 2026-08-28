<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    // For central domain: axispro-sms.onrender.com / platform
    public function centralHome(): View
    {
        return view('platform.home');
    }

    // For tenant routes: / and /school-home
    public function index(): View
    {
        if (!app()->bound('currentSchool')) {
            // No subdomain matched a school at all - this is AxisPro's own
            // central marketing page, not any customer's homepage.
            return view('platform.home');
        }

        $school = app('currentSchool');

        if ($school->subdomain === 'royalcountrysideacademy') {
            return view('home');
        }

        return view('home-generic', ['school' => $school]);
    }
}
