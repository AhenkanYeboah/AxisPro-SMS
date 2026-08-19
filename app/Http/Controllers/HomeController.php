<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Display the Central Marketing & Platform Landing Page.
     * Served at the root URL (/) for central domain requests.
     */
    public function centralHome(Request $request): View
    {
        // Safety check: if a school/tenant context is active, render tenant view or redirect
        if (app()->bound('currentTenant')) {
            $tenant = app('currentTenant');
            return view('school.home', compact('tenant'));
        }

        return view('central.landing', [
            'appName' => config('app.name'),
        ]);
    }

    /**
     * Display the Tenant / School Homepage.
     * Served for tenant subdomains or specific school landing routes.
     */
    public function index(Request $request): View
    {
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

        return view('school.home', compact('tenant'));
    }
}
