<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        // Safe container check that verifies 'currentSchool' exists and is not null
        $school = app()->has('currentSchool') ? app('currentSchool') : null;

        if (!$school) {
            // No subdomain/tenant matched a school - return central marketing page
            return view('platform.home');
        }

        // Royal Countryside Academy legacy view check (handles slug or subdomain)
        $identifier = $school->subdomain ?? $school->slug ?? '';
        if ($identifier === 'royalcountrysideacademy') {
            return view('home');
        }

        return view('home-generic', ['school' => $school]);
    }
}
