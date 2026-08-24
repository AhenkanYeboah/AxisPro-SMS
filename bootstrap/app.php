<?php

use App\Http\Middleware\EnsureStudentAdmitted;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Teacher routes
            if (file_exists(base_path('routes/teacher.php'))) {
                Route::middleware('web')
                    ->prefix('teacher')
                    ->name('teacher.')
                    ->group(base_path('routes/teacher.php'));
            }

            // Student routes
            if (file_exists(base_path('routes/student.php'))) {
                Route::middleware('web')
                    ->prefix('student')
                    ->name('student.')
                    ->group(base_path('routes/student.php'));
            }

            // Admin routes
            if (file_exists(base_path('routes/admin.php'))) {
                Route::middleware('web')
                    ->prefix('admin')
                    ->name('admin.')
                    ->group(base_path('routes/admin.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('teacher/*')) {
                return route('teacher.login');
            }
            if ($request->is('student/*')) {
                return route('student.login');
            }

            return route('admin.login');
        });

        // Multi-tenancy: figure out which school this request belongs to
        // (from the subdomain) BEFORE any controller/model code runs.
        $middleware->web(append: [ResolveTenant::class]);

        // Gates the real student portal to admitted students.
        $middleware->alias([
            'admitted' => EnsureStudentAdmitted::class,
        ]);

        // Paystack webhook exemption from CSRF
        $middleware->validateCsrfTokens(except: [
            'paystack/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
