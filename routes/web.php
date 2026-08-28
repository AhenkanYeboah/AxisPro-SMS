<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminClassLevelController;
use App\Http\Controllers\AdminExemplarController;
use App\Http\Controllers\AdminInviteController;
use App\Http\Controllers\AdminTeacherController;
use App\Http\Controllers\AdminExamController;
use App\Http\Controllers\AdminStudentController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AssignmentSubmissionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminFeeController;
use App\Http\Controllers\AdminLibraryController;
use App\Http\Controllers\AdminNoticeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\Platform\PlatformBillingController;
use App\Http\Controllers\Platform\CurriculumDocumentController;
use App\Http\Controllers\Platform\CurriculumExemplarController;
use App\Http\Controllers\PlatformAuthController;
use App\Http\Controllers\PlatformDashboardController;
use App\Http\Controllers\ResearchAssistantController;
use App\Http\Controllers\StudentDashboardFeeController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\SchoolSettingsController;
use App\Http\Controllers\SchoolSignupController;
use App\Http\Controllers\StorageFallbackController;
use App\Http\Controllers\StudentAssignmentController;
use App\Http\Controllers\StudentAuthController;
use App\Http\Controllers\StudentVirtualClassController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentExamController;
use App\Http\Controllers\StudentLibraryController;
use App\Http\Controllers\TeacherAuthController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\TeacherLibraryController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\VirtualClassController;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Support\Facades\Route;

// Platform domain - ONLY platform routes, no '/' here
Route::domain(config('app.central_domain', parse_url(config('app.url'), PHP_URL_HOST)))->group(function () {
    Route::get('/platform', [HomeController::class, 'centralHome'])->name('platform.home');
    Route::get('/storage/{path}', [StorageFallbackController::class, 'show'])->where('path', '.*')->name('storage.fallback');
    Route::get('/signup', [SchoolSignupController::class, 'create'])->name('school.signup');
    Route::post('/signup', [SchoolSignupController::class, 'store'])->name('school.signup.store');
    Route::get('/signup/{school}/success', [SchoolSignupController::class, 'success'])->name('school.signup.success');
    Route::post('/paystack/webhook', [PaystackWebhookController::class, 'handle'])->name('paystack.webhook');
    Route::get('/platform/login', [PlatformAuthController::class, 'showLogin'])->name('platform.login');
    Route::post('/platform/login', [PlatformAuthController::class, 'login'])->middleware('throttle:6,1')->name('platform.login.submit');
    Route::post('/platform/logout', [PlatformAuthController::class, 'logout'])->name('platform.logout');
    Route::middleware('auth:platform')->group(function () {
        Route::get('/platform/dashboard', [PlatformDashboardController::class, 'index'])->name('platform.dashboard');
        Route::get('/platform/schools/{school}', [PlatformDashboardController::class, 'show'])->name('platform.schools.show');
        Route::post('/platform/schools/{school}/suspend', [PlatformDashboardController::class, 'suspend'])->name('platform.schools.suspend');
        Route::post('/platform/schools/{school}/reactivate', [PlatformDashboardController::class, 'reactivate'])->name('platform.schools.reactivate');
        Route::post('/platform/schools/{school}/extend-trial', [PlatformDashboardController::class, 'extendTrial'])->name('platform.schools.extend-trial');
        Route::get('/platform/schools/{school}/billing', [PlatformBillingController::class, 'show'])->name('platform.billing.show');
        Route::post('/platform/schools/{school}/billing/checkout', [PlatformBillingController::class, 'checkout'])->name('platform.billing.checkout');
        Route::get('/platform/curriculum-documents', [CurriculumDocumentController::class, 'index'])->name('platform.curriculum-documents.index');
        Route::post('/platform/curriculum-documents', [CurriculumDocumentController::class, 'store'])->name('platform.curriculum-documents.store');
    });
    Route::get('/platform/schools/{school}/billing/callback', [PlatformBillingController::class, 'callback'])->name('platform.billing.callback');
});

// Tenant + public routes - SINGLE smart '/'
Route::middleware(['web', ResolveTenant::class])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('school.home');
    Route::get('/school-home', [HomeController::class, 'index']);
    Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');

    Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:6,1')->name('admin.login.submit');
    Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::middleware('auth:admin')->group(function () {
        Route::get('/admin/dashboard', [AdminStudentController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/admin/settings', [SchoolSettingsController::class, 'show'])->name('admin.settings');
        Route::post('/admin/settings', [SchoolSettingsController::class, 'update'])->name('admin.settings.update');
        Route::get('/admin/students/{student}', [AdminStudentController::class, 'show'])->name('admin.students.show');
        Route::post('/admin/students/{student}/exam-date', [AdminStudentController::class, 'setExamDate'])->name('admin.students.exam-date');
        Route::post('/admin/students/{student}/exam-completed', [AdminStudentController::class, 'markExamCompleted'])->name('admin.students.exam-completed');
        Route::post('/admin/students/{student}/verify', [AdminStudentController::class, 'verify'])->name('admin.students.verify');
        Route::post('/admin/students/{student}/decline', [AdminStudentController::class, 'decline'])->name('admin.students.decline');
        Route::delete('/admin/students/{student}', [AdminStudentController::class, 'destroy'])->name('admin.students.destroy');
        Route::get('/admin/exams', [AdminExamController::class, 'index'])->name('admin.exams.index');
        Route::post('/admin/exams', [AdminExamController::class, 'store'])->name('admin.exams.store');
        Route::get('/admin/fees', [AdminFeeController::class, 'index'])->name('admin.fees.index');
        Route::post('/admin/fees', [AdminFeeController::class, 'store'])->name('admin.fees.store');
        Route::post('/activities', [ActivityController::class, 'store'])->name('activities.store');
        Route::put('/activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
        Route::delete('/activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');
    });

    Route::get('/teacher/login', [TeacherAuthController::class, 'showLogin'])->name('teacher.login');
    Route::post('/teacher/login', [TeacherAuthController::class, 'login'])->name('teacher.login.submit');
    Route::get('/student/login', [StudentAuthController::class, 'showLogin'])->name('student.login');
    Route::post('/student/login', [StudentAuthController::class, 'login'])->name('student.login.submit');
    Route::get('/student/register', [StudentAuthController::class, 'showRegister'])->name('student.form');
    Route::post('/student/register', [StudentAuthController::class, 'register'])->name('student.register');
});
