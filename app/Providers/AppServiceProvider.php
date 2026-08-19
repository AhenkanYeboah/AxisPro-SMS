<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Models\School;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the currentSchool singleton container binding
        $this->app->singleton('currentSchool', function ($app) {
            // 1. Check if an authenticated school admin exists
            if (auth('admin')->check() && auth('admin')->user()->school_id) {
                return School::find(auth('admin')->user()->school_id);
            }

            // 2. Fallback to active session tenant ID if present
            if (session()->has('active_school_id')) {
                return School::find(session('active_school_id'));
            }

            return null;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production' || env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }
    }
}
