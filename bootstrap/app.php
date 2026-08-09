<?php

use App\Http\Middleware\EnsureStudentAdmitted;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // No custom middleware needed here - Laravel's built-in 'auth' middleware
        // is guard-aware out of the box, so routes/web.php uses auth:admin,
        // auth:teacher, and auth:student directly. Each automatically redirects
        // to the matching login route because of $middleware->redirectGuestsTo()
        // below.
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
        // (from the subdomain) BEFORE any controller/model code runs, so
        // every tenant-scoped model query is automatically filtered to that
        // school for the rest of the request. Runs on every web request.
        $middleware->web(append: [ResolveTenant::class]);

        // Gates the real student portal (dashboard, assignments, fees,
        // etc.) to students an admin has actually admitted - see
        // EnsureStudentAdmitted's docblock. Registered as an alias so
        // routes/web.php can write 'admitted' rather than the FQCN.
        $middleware->alias([
            'admitted' => EnsureStudentAdmitted::class,
        ]);

        // Paystack's webhook is a server-to-server POST - it can never carry
        // a CSRF token, so it must be exempted here or every webhook delivery
        // would be rejected with a 419. Its authenticity is instead verified
        // via HMAC signature inside PaystackWebhookController itself.
        $middleware->validateCsrfTokens(except: [
            'paystack/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
