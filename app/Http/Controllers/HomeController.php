<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    // Always platform marketing page
    public function centralHome(): View
    {
        return view('platform.home');
    }

    // Smart homepage: if tenant resolved -> school homepage, else platform
    public function index(): View
    {
        if (!app()->bound('currentSchool')) {
            return view('platform.home');
        }

        $school = app('currentSchool');

        // Royal Countryside Academy keeps its original hand-built homepage
        if ($school->subdomain === 'royalcountrysideacademy') {
            return view('home');
        }

        return view('home-generic', ['school' => $school]);
    }
}
