<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        if (!app()->bound('currentSchool')) {
            // No subdomain matched a school at all - this is AxisPro's own
            // central marketing page, not any customer's homepage.
            return view('platform.home');
        }

        $school = app('currentSchool');

        // Royal Countryside Academy is the original single-school build
        // this whole product grew out of. Their homepage (resources/views/
        // home.blade.php) is preserved hand-built and untouched rather than
        // retrofitted into the generic per-school template below, so
        // nothing about their page shifts as a side effect of every other
        // school getting one. Every school after them gets the dynamic
        // template, branded from their own Settings.
        if ($school->subdomain === 'royalcountrysideacademy') {
            return view('home');
        }

        return view('home-generic', ['school' => $school]);
    }
}
