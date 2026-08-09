<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StudentAuthController extends Controller
{
    public function showForm(): View
    {
        return view('student.form');
    }

    // Replaces the big `action == 'register'` block. File-upload validation that
    // your code did by hand (mime type checks, size limits, safe filenames,
    // mkdir) is now declared as validation rules - Laravel enforces them and
    // ->store() handles safe naming and directory creation for you.
    public function register(Request $request): RedirectResponse
    {
        $requiresResults = in_array($request->input('class'), Student::classesRequiringResults());

        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:Male,Female',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'region' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'next_of_kin' => 'nullable|string|max:150',
            'class' => 'required|string|max:50',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048', // 2MB
            'results_file' => ($requiresResults ? 'required' : 'nullable').'|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120', // 5MB
        ]);

        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = $request->file('profile_image')->store('uploads', 'public');
        }

        if ($request->hasFile('results_file')) {
            $data['results_file'] = $request->file('results_file')->store('uploads/results', 'public');
        }

        // Generate a unique ROCAS###### student ID.
        do {
            $studentId = 'ROCAS'.sprintf('%06d', random_int(100000, 999999));
        } while (Student::where('student_id', $studentId)->exists());

        $data['student_id'] = $studentId;
        $data['status'] = 'pending';

        Student::create($data);

        return redirect()->route('student.set-password', ['id' => $studentId]);
    }

    public function showSetPassword(): View
    {
        return view('auth.student_set_password');
    }

    public function setPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $student = Student::where('student_id', $data['student_id'])->first();

        if (!$student) {
            return back()->withErrors(['student_id' => 'Invalid Student ID.'])->withInput();
        }

        if (!empty($student->password)) {
            return back()->withErrors(['student_id' => 'Password already set. Please log in.'])->withInput();
        }

        $student->password = $data['password'];
        $student->save();

        Auth::guard('student')->login($student);
        $request->session()->regenerate();

        return redirect()->route('student.dashboard');
    }

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('student')->check()) {
            return redirect()->route('student.dashboard');
        }

        return view('auth.student_login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'student_id' => 'required|string',
            'password' => 'required|string',
        ]);

        $student = Student::where('student_id', $credentials['student_id'])->first();

        if (!$student || !$student->password || !Hash::check($credentials['password'], $student->password)) {
            return back()->withErrors(['student_id' => 'Invalid Student ID or password.'])->withInput();
        }

        if ($student->status === 'declined') {
            return back()->withErrors(['student_id' => 'Your application was declined.']);
        }

        Auth::guard('student')->login($student);
        $request->session()->regenerate();

        return redirect()->route('student.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('student')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('student.login');
    }

    // Where EnsureStudentAdmitted sends a logged-in student who isn't
    // admitted yet. Handles both real states that land here: 'pending'
    // (still under review - the normal case right after registering) and
    // 'declined' (login() already blocks a fresh login for this status,
    // but an existing session predating a decline could still reach here,
    // so it's handled rather than assumed impossible).
    public function applicationStatus(): View
    {
        $student = Auth::guard('student')->user();

        return view('student.application-status', [
            'student' => $student,
            'canTakeExam' => $student->exam_id && ! $student->exam_completed,
        ]);
    }
}
