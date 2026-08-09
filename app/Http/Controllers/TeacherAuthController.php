<?php

namespace App\Http\Controllers;

use App\Mail\TeacherVerificationCodeMail;
use App\Models\Invite;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class TeacherAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('teacher')->check()) {
            return redirect()->route('teacher.dashboard');
        }

        return view('auth.teacher_login');
    }

    // STEP 1: verify username/email/ID + password, then generate a one-time code.
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Accept username, email, or the ROCAT#### ID in the same field.
        $teacher = Teacher::where('username', $credentials['username'])
            ->orWhere('email', $credentials['username'])
            ->orWhere('teacher_id', $credentials['username'])
            ->first();

        if (!$teacher || !$teacher->password || !Hash::check($credentials['password'], $teacher->password)) {
            return back()->withErrors(['username' => 'Username, email, or ID not found, or incorrect password.'])->withInput();
        }

        $code = sprintf('%06d', random_int(100000, 999999));

        $request->session()->put('teacher_verify_id', $teacher->id);
        $request->session()->put('teacher_verify_code', $code);
        $request->session()->put('teacher_verify_time', now()->timestamp);

        // Best practice: actually deliver the code by email rather than
        // displaying it on the same screen the person is looking at (that
        // defeats the point of a second factor). Only in local development,
        // where MAIL_MAILER is typically 'log' and there's no real inbox to
        // check, do we also flash it to the page so the flow is testable.
        try {
            Mail::to($teacher->email)->send(new TeacherVerificationCodeMail($code, $teacher->full_name, $teacher->school->name));
        } catch (\Throwable $e) {
            Log::warning('Failed to send teacher verification code email', [
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('teacher.verify')->with(
            app()->environment('local') ? ['dev_code_preview' => $code] : []
        )->with('status', 'A verification code has been sent to your email.');
    }

    public function showVerify(): View|RedirectResponse
    {
        if (!session()->has('teacher_verify_id')) {
            return redirect()->route('teacher.login');
        }

        return view('auth.teacher_verify');
    }

    // STEP 2: check the 6-digit code (5 minute expiry) + re-confirm password.
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string',
            'password' => 'required|string',
        ]);

        $teacherId = $request->session()->get('teacher_verify_id');
        $storedCode = $request->session()->get('teacher_verify_code');
        $codeTime = $request->session()->get('teacher_verify_time');

        if (!$teacherId || !$storedCode) {
            return redirect()->route('teacher.login')->withErrors(['code' => 'Verification session expired. Please log in again.']);
        }

        if (now()->timestamp - $codeTime > 300) {
            $request->session()->forget(['teacher_verify_id', 'teacher_verify_code', 'teacher_verify_time']);

            return redirect()->route('teacher.login')->withErrors(['code' => 'Verification code expired. Please log in again.']);
        }

        if (!hash_equals($storedCode, $request->input('code'))) {
            return back()->withErrors(['code' => 'Incorrect verification code.']);
        }

        $teacher = Teacher::findOrFail($teacherId);

        if (!Hash::check($request->input('password'), $teacher->password)) {
            return back()->withErrors(['password' => 'Invalid password. Please try again.']);
        }

        $request->session()->forget(['teacher_verify_id', 'teacher_verify_code', 'teacher_verify_time']);

        Auth::guard('teacher')->login($teacher);
        $request->session()->regenerate();

        return redirect()->route('teacher.dashboard');
    }

    // First-time password setup for a teacher account created by admin (no password yet).
    public function showSetPassword(): View
    {
        return view('auth.teacher_set_password');
    }

    public function setPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'teacher_id' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $teacher = Teacher::where('teacher_id', $data['teacher_id'])->first();

        if (!$teacher) {
            return back()->withErrors(['teacher_id' => 'Invalid Teacher ID.'])->withInput();
        }

        if (!empty($teacher->password)) {
            return back()->withErrors(['teacher_id' => 'Password already set. Please log in.'])->withInput();
        }

        $teacher->password = $data['password']; // hashed automatically
        $teacher->save();

        Auth::guard('teacher')->login($teacher);
        $request->session()->regenerate();

        return redirect()->route('teacher.dashboard');
    }

    public function showSignup(): View
    {
        return view('auth.teacher_signup', [
            // Real classes, not the old hardcoded string list - see
            // signup() below for why this replaced 'assigned_class' as a
            // free string.
            'classLevels' => \App\Models\ClassLevel::orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    // Replaces the isset($_POST['teacher_signup']) block. The old shared
    // 'LimenSpoon' key let anyone with the (hardcoded, unrevocable) string
    // register as a teacher. Now an admin has to generate a single-use
    // invite code per teacher from /admin/invites first.
    public function signup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => 'required|string|max:50|unique:teachers,username',
            'email' => 'required|email|max:100|unique:teachers,email',
            'full_name' => 'required|string|max:100',
            // Was 'assigned_class' => free string, matched against no real
            // table - a teacher could type/select anything, and nothing
            // ever set class_level_id, which is exactly what the research
            // assistant (and every other curriculum-aware feature) reads.
            // This now requires picking a real class_levels row.
            'class_level_id' => 'required|exists:class_levels,id',
            'invite_code' => 'required|string',
            'teacher_profile_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ]);

        $invite = Invite::where('type', 'teacher')->where('code', $data['invite_code'])->first();

        if (!$invite || !$invite->isValid($data['email'])) {
            return back()
                ->withErrors(['invite_code' => 'That invite code is invalid, expired, already used, or was issued for a different email.'])
                ->withInput();
        }

        $classLevel = \App\Models\ClassLevel::findOrFail($data['class_level_id']);

        do {
            $teacherId = 'ROCAT'.sprintf('%06d', random_int(100000, 999999));
        } while (Teacher::where('teacher_id', $teacherId)->exists());

        $profileImage = null;
        if ($request->hasFile('teacher_profile_image')) {
            $profileImage = $request->file('teacher_profile_image')->store('uploads/teachers', 'public');
        }

        $teacher = Teacher::create([
            'teacher_id' => $teacherId,
            'username' => $data['username'],
            'email' => $data['email'],
            'full_name' => $data['full_name'],
            // Both set from the same ClassLevel, deliberately kept in
            // sync: 'assigned_class' (the display name) is still what
            // attendance/timetable/assignments/report cards read (see
            // those controllers - left as-is, not in scope to migrate
            // right now), while 'class_level_id' is what the research
            // assistant and other curriculum-aware features read.
            'assigned_class' => $classLevel->name,
            'class_level_id' => $classLevel->id,
            'profile_image' => $profileImage,
            // password left null - teacher sets it next, same as admin-created accounts
        ]);

        $invite->markUsedByTeacher($teacher);

        return redirect()->route('teacher.set-password', ['id' => $teacherId])
            ->with('status', 'Teacher account created! Please set your password.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('teacher')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('teacher.login');
    }
}
