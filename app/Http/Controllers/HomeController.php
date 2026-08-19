<?php

namespace App\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Display the Central Platform Landing Page.
     */
    public function index(Request $request): View
    {
        // Explicitly check if a tenant is accidentally bound in context
        if (app()->bound('currentTenant')) {
            $tenant = app('currentTenant');
            
            // Optional safety fallback: redirect or render tenant view if routed incorrectly
            return view('tenant.landing', compact('tenant'));
        }

        // Render the central platform landing page view
        return view('platform.landing', [
            'appName' => config('app.name'),
        ]);
    }
}
