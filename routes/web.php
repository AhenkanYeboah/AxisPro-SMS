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
use App\Http\Controllers\StudentAssignmentController;
use App\Http\Controllers\StudentAuthController;
use App\Http\Controllers\StudentVirtualClassController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentExamController;
use App\Http\Controllers\TeacherAuthController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\VirtualClassController;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| CENTRAL / PLATFORM ROUTES (Unscoped / Platform Domain)
|--------------------------------------------------------------------------
| These routes serve the main marketing platform, SaaS onboarding,
| platform management, and system-wide webhooks/assets.
|
*/
Route::domain(config('app.central_domain', parse_url(config('app.url'), PHP_URL_HOST)))->group(function () {

    // ──────────────────────────────────────────────────────────────
    // CENTRAL MARKETING & PUBLIC LANDING
    // ──────────────────────────────────────────────────────────────
    Route::get('/', [HomeController::class, 'centralHome'])->name('home');

    // ──────────────────────────────────────────────────────────────
    // UPLOADED FILE FALLBACK
    // ──────────────────────────────────────────────────────────────
    Route::get('/storage/{path}', function (string $path) {
        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path);
    })->where('path', '.*')->name('storage.fallback');

    // ──────────────────────────────────────────────────────────────
    // SCHOOL SIGNUP (SaaS Onboarding)
    // ──────────────────────────────────────────────────────────────
    Route::get('/signup', [SchoolSignupController::class, 'create'])->name('school.signup');
    Route::post('/signup', [SchoolSignupController::class, 'store'])->name('school.signup.store');
    Route::get('/signup/{school}/success', [SchoolSignupController::class, 'success'])->name('school.signup.success');

    // ──────────────────────────────────────────────────────────────
    // SYSTEM WEBHOOKS
    // ──────────────────────────────────────────────────────────────
    Route::post('/paystack/webhook', [PaystackWebhookController::class, 'handle'])->name('paystack.webhook');

    // ──────────────────────────────────────────────────────────────
    // PLATFORM ADMIN ROUTING
    // Central owner control panel isolated from individual school scopes.
    // ──────────────────────────────────────────────────────────────
    Route::get('/platform/login', [PlatformAuthController::class, 'showLogin'])->name('platform.login');
    Route::post('/platform/login', [PlatformAuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('platform.login.submit');
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
        Route::get('/platform/curriculum-documents/{document}', [CurriculumDocumentController::class, 'show'])->name('platform.curriculum-documents.show');
        Route::post('/platform/curriculum-documents/{document}/reingest', [CurriculumDocumentController::class, 'reingest'])->name('platform.curriculum-documents.reingest');
        Route::delete('/platform/curriculum-documents/{document}', [CurriculumDocumentController::class, 'destroy'])->name('platform.curriculum-documents.destroy');
        Route::put('/platform/curriculum-document-chunks/{chunk}', [CurriculumDocumentController::class, 'updateChunk'])->name('platform.curriculum-document-chunks.update');
        Route::delete('/platform/curriculum-document-chunks/{chunk}', [CurriculumDocumentController::class, 'destroyChunk'])->name('platform.curriculum-document-chunks.destroy');

        Route::get('/platform/curriculum-exemplars', [CurriculumExemplarController::class, 'index'])->name('platform.curriculum-exemplars.index');
        Route::post('/platform/curriculum-exemplars', [CurriculumExemplarController::class, 'store'])->name('platform.curriculum-exemplars.store');
        Route::post('/platform/research-requests/{researchRequest}/promote', [CurriculumExemplarController::class, 'promote'])->name('platform.research-requests.promote');
        Route::delete('/platform/curriculum-exemplars/{exemplar}', [CurriculumExemplarController::class, 'destroy'])->name('platform.curriculum-exemplars.destroy');
    });

    Route::get('/platform/schools/{school}/billing/callback', [PlatformBillingController::class, 'callback'])->name('platform.billing.callback');

});

/*
|--------------------------------------------------------------------------
| TENANT / SCHOOL ROUTES (Scoped by ResolveTenant)
|--------------------------------------------------------------------------
| These routes handle school subdomains/domains and run through the
| ResolveTenant middleware to set active school context.
|
*/
Route::middleware(['web', ResolveTenant::class])->group(function () {

    // ──────────────────────────────────────────────────────────────
    // TENANT SCHOOL HOMEPAGE & PUBLIC PAGES
    // ──────────────────────────────────────────────────────────────
    Route::get('/', [HomeController::class, 'index'])->name('school.home');
    Route::get('/school-home', [HomeController::class, 'index']); // Kept for legacy redirect compatibility
    Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');

    // ──────────────────────────────────────────────────────────────
    // STUDENT: Registration, Auth, and Dashboard
    // ──────────────────────────────────────────────────────────────
    Route::get('/enroll', [StudentAuthController::class, 'showForm'])->name('student.form');
    Route::post('/enroll', [StudentAuthController::class, 'register'])->name('student.register');

    Route::get('/set-password', [StudentAuthController::class, 'showSetPassword'])->name('student.set-password');
    Route::post('/set-password', [StudentAuthController::class, 'setPassword'])->name('student.set-password.submit');

    Route::get('/student/login', [StudentAuthController::class, 'showLogin'])->name('student.login');
    Route::post('/student/login', [StudentAuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('student.login.submit');
    Route::post('/student/logout', [StudentAuthController::class, 'logout'])->name('student.logout');

    Route::middleware('auth:student')->group(function () {
        Route::get('/student/exam', [StudentExamController::class, 'show'])->name('student.exam');
        Route::post('/student/exam', [StudentExamController::class, 'submit'])->name('student.exam.submit');
        Route::get('/student/application-status', [StudentAuthController::class, 'applicationStatus'])->name('student.application-status');

        Route::middleware('admitted')->group(function () {
            Route::get('/student/dashboard', [StudentDashboardController::class, 'index'])->name('student.dashboard');
            Route::get('/student/assignments', [StudentAssignmentController::class, 'index'])->name('student.assignments');
            Route::post('/student/assignments/{assignment}/submit', [StudentAssignmentController::class, 'submit'])->name('student.assignments.submit');
            Route::get('/student/results', [StudentAssignmentController::class, 'results'])->name('student.results');
            Route::get('/student/timetable', [TimetableController::class, 'studentIndex'])->name('student.timetable');
            Route::get('/student/report-card', [ReportCardController::class, 'studentIndex'])->name('student.report-card');
            Route::get('/student/fees', [StudentDashboardFeeController::class, 'index'])->name('student.fees');
            Route::get('/student/virtual-classes', [StudentVirtualClassController::class, 'index'])->name('student.virtual-classes');
            Route::get('/student/virtual-classes/{virtualClass}/join', [StudentVirtualClassController::class, 'join'])->name('student.virtual-classes.join');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // TEACHER: Authentication, Dashboard, and AI Assistant
    // ──────────────────────────────────────────────────────────────
    Route::get('/teacher/login', [TeacherAuthController::class, 'showLogin'])->name('teacher.login');
    Route::post('/teacher/login', [TeacherAuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('teacher.login.submit');

    Route::get('/teacher/signup', [TeacherAuthController::class, 'showSignup'])->name('teacher.signup');
    Route::post('/teacher/signup', [TeacherAuthController::class, 'signup'])
        ->middleware('throttle:6,1')
        ->name('teacher.signup.submit');

    Route::get('/teacher/verify', [TeacherAuthController::class, 'showVerify'])->name('teacher.verify');
    Route::post('/teacher/verify', [TeacherAuthController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('teacher.verify.submit');

    Route::get('/teacher/set-password', [TeacherAuthController::class, 'showSetPassword'])->name('teacher.set-password');
    Route::post('/teacher/set-password', [TeacherAuthController::class, 'setPassword'])->name('teacher.set-password.submit');
    Route::post('/teacher/logout', [TeacherAuthController::class, 'logout'])->name('teacher.logout');

    Route::middleware('auth:teacher')->group(function () {
        Route::get('/teacher/dashboard', [TeacherDashboardController::class, 'index'])->name('teacher.dashboard');
        Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

        Route::get('/teacher/assignments', [AssignmentController::class, 'teacherIndex'])->name('teacher.assignments');
        Route::get('/teacher/assignments/{assignment}/submissions', [AssignmentSubmissionController::class, 'index'])->name('teacher.assignments.submissions');
        Route::post('/teacher/submissions/{submission}/mark', [AssignmentSubmissionController::class, 'mark'])->name('teacher.submissions.mark');

        Route::get('/teacher/timetable', [TimetableController::class, 'teacherIndex'])->name('teacher.timetable');
        Route::post('/teacher/timetable', [TimetableController::class, 'store'])->name('teacher.timetable.store');
        Route::delete('/teacher/timetable/{timetable}', [TimetableController::class, 'destroy'])->name('teacher.timetable.destroy');

        Route::get('/teacher/report-cards', [ReportCardController::class, 'teacherIndex'])->name('teacher.report-cards');
        Route::post('/teacher/report-cards', [ReportCardController::class, 'store'])->name('teacher.report-cards.store');
        Route::delete('/teacher/report-cards/{reportCard}', [ReportCardController::class, 'destroy'])->name('teacher.report-cards.destroy');

        Route::get('/teacher/attendance', [AttendanceController::class, 'index'])->name('teacher.attendance');
        Route::post('/teacher/attendance', [AttendanceController::class, 'save'])->name('teacher.attendance.save');

        Route::post('/teacher/students/{student}/promote', [TeacherDashboardController::class, 'promote'])->name('teacher.students.promote');
        Route::post('/teacher/students/{student}/repeat', [TeacherDashboardController::class, 'repeat'])->name('teacher.students.repeat');

        Route::get('/teacher/research-assistant', [ResearchAssistantController::class, 'index'])->name('teacher.research-assistant');
        Route::post('/teacher/research-assistant', [ResearchAssistantController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('teacher.research-assistant.store');
        Route::post('/teacher/research-assistant/{researchRequest}/helpful', [ResearchAssistantController::class, 'markHelpful'])->name('teacher.research-assistant.helpful');

        Route::get('/teacher/virtual-classes', [VirtualClassController::class, 'index'])->name('teacher.virtual-classes');
        Route::post('/teacher/virtual-classes', [VirtualClassController::class, 'store'])->name('teacher.virtual-classes.store');
        Route::post('/teacher/virtual-classes/{virtualClass}/cancel', [VirtualClassController::class, 'cancel'])->name('teacher.virtual-classes.cancel');
    });

    // ──────────────────────────────────────────────────────────────
    // SCHOOL ADMIN: Portal Management
    // ──────────────────────────────────────────────────────────────
    Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('admin.login.submit');
    Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::middleware('auth:admin')->group(function () {
        Route::get('/admin/dashboard', [AdminStudentController::class, 'dashboard'])->name('admin.dashboard');

        Route::get('/admin/settings', [SchoolSettingsController::class, 'show'])->name('admin.settings');
        Route::post('/admin/settings', [SchoolSettingsController::class, 'update'])->name('admin.settings.update');
        Route::post('/admin/settings/curricula', [SchoolSettingsController::class, 'updateCurricula'])->name('admin.settings.curricula');

        Route::get('/admin/class-levels', [AdminClassLevelController::class, 'index'])->name('admin.class-levels.index');
        Route::post('/admin/class-levels', [AdminClassLevelController::class, 'store'])->name('admin.class-levels.store');
        Route::put('/admin/class-levels/{classLevel}', [AdminClassLevelController::class, 'update'])->name('admin.class-levels.update');
        Route::delete('/admin/class-levels/{classLevel}', [AdminClassLevelController::class, 'destroy'])->name('admin.class-levels.destroy');

        Route::get('/admin/teachers', [AdminTeacherController::class, 'index'])->name('admin.teachers.index');
        Route::put('/admin/teachers/{teacher}', [AdminTeacherController::class, 'update'])->name('admin.teachers.update');

        Route::get('/admin/exemplars', [AdminExemplarController::class, 'index'])->name('admin.exemplars.index');
        Route::post('/admin/exemplars', [AdminExemplarController::class, 'store'])->name('admin.exemplars.store');
        Route::post('/admin/research-requests/{researchRequest}/promote', [AdminExemplarController::class, 'promote'])->name('admin.research-requests.promote');
        Route::delete('/admin/exemplars/{exemplar}', [AdminExemplarController::class, 'destroy'])->name('admin.exemplars.destroy');

        Route::get('/admin/invites', [AdminInviteController::class, 'index'])->name('admin.invites.index');
        Route::post('/admin/invites', [AdminInviteController::class, 'store'])->name('admin.invites.store');
        Route::delete('/admin/invites/{invite}', [AdminInviteController::class, 'destroy'])->name('admin.invites.destroy');
        Route::get('/admin/students/{student}', [AdminStudentController::class, 'show'])->name('admin.students.show');
        Route::post('/admin/students/{student}/exam-date', [AdminStudentController::class, 'setExamDate'])->name('admin.students.exam-date');
        Route::post('/admin/students/{student}/exam-completed', [AdminStudentController::class, 'markExamCompleted'])->name('admin.students.exam-completed');
        Route::post('/admin/students/{student}/verify', [AdminStudentController::class, 'verify'])->name('admin.students.verify');
        Route::post('/admin/students/{student}/decline', [AdminStudentController::class, 'decline'])->name('admin.students.decline');
        Route::delete('/admin/students/{student}', [AdminStudentController::class, 'destroy'])->name('admin.students.destroy');

        Route::get('/admin/exams', [AdminExamController::class, 'index'])->name('admin.exams.index');
        Route::post('/admin/exams', [AdminExamController::class, 'store'])->name('admin.exams.store');
        Route::get('/admin/exams/{exam}', [AdminExamController::class, 'show'])->name('admin.exams.show');
        Route::delete('/admin/exams/{exam}', [AdminExamController::class, 'destroy'])->name('admin.exams.destroy');
        Route::post('/admin/exam-submissions/{submission}/grade', [AdminExamController::class, 'grade'])->name('admin.exam-submissions.grade');

        Route::post('/activities', [ActivityController::class, 'store'])->name('activities.store');
        Route::put('/activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
        Route::delete('/activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

        Route::get('/admin/fees', [AdminFeeController::class, 'index'])->name('admin.fees.index');
        Route::post('/admin/fees', [AdminFeeController::class, 'store'])->name('admin.fees.store');
        Route::delete('/admin/fees/{feeItem}', [AdminFeeController::class, 'destroy'])->name('admin.fees.destroy');
        Route::post('/admin/fees/{feeItem}/assign', [AdminFeeController::class, 'assign'])->name('admin.fees.assign');

        Route::get('/admin/student-fees/{studentFee}', [AdminFeeController::class, 'showStudentFee'])->name('admin.student-fees.show');
        Route::post('/admin/student-fees/{studentFee}/payments', [AdminFeeController::class, 'recordPayment'])->name('admin.student-fees.payments.store');
        Route::post('/admin/student-fees/{studentFee}/waive', [AdminFeeController::class, 'waive'])->name('admin.student-fees.waive');

        Route::get('/admin/notices', [AdminNoticeController::class, 'index'])->name('admin.notices.index');
        Route::post('/admin/notices', [AdminNoticeController::class, 'store'])->name('admin.notices.store');
        Route::get('/admin/notices/{notice}', [AdminNoticeController::class, 'show'])->name('admin.notices.show');
    });

});
