@extends('layouts.dashboard')

@section('title', 'Enrollment Form')
@section('sidebar-sub', 'Admissions · 2025/2026')
@section('page-label', 'Admissions')
@section('welcome-message', 'Enrollment Form')

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.form') }}" class="active"><i class="nav-icon">✎</i> Enrollment Form</a>
@endsection

@section('content')
    @if ($errors->any())
        <div class="message error">
            ❌ {{ $errors->first() }}
        </div>
    @endif

    <div class="card card-padded">
        {{-- enctype="multipart/form-data" is required whenever a form includes
             a file input - same rule in plain HTML as it was in your original file. --}}
        <form action="{{ route('student.register') }}" method="POST" enctype="multipart/form-data" id="enrollmentForm">
            @csrf

            <p class="form-section-title">Passport Photo</p>
            <div class="form-group">
                <label>Profile Image</label>
                <input type="file" name="profile_image" accept="image/*">
                <p style="font-size:11px; color:var(--muted); margin-top:4px;">JPG, PNG, GIF, or WEBP. Max 2MB.</p>
            </div>

            <p class="form-section-title">Personal Information</p>
            <div class="form-row">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required placeholder="e.g., Kwame">
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name') }}" placeholder="Optional">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required placeholder="e.g., Mensah">
                </div>
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="example@email.com">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender">
                        <option value="">Select gender</option>
                        <option {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                        <option {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="+233 XX XXX XXXX">
                </div>
                <div class="form-group">
                    <label>Next of Kin (Parent / Guardian)</label>
                    <input type="text" name="next_of_kin" value="{{ old('next_of_kin') }}" placeholder="Full name">
                </div>
            </div>
            <div class="form-group">
                <label>Home Address in Ghana</label>
                <textarea name="address" rows="2" placeholder="House number, street, area...">{{ old('address') }}</textarea>
            </div>

            <div class="form-group">
                <label>Class / Grade *</label>
                <select name="class" id="classSelect" required>
                    <option value="">Select Class</option>
                    @foreach (['Creche','Nursery','Kindergarten','Primary 1','Primary 2','Primary 3','Primary 4','Primary 5','Primary 6','JHS 1','JHS 2','JHS 3'] as $class)
                        <option value="{{ $class }}" {{ old('class') == $class ? 'selected' : '' }}>{{ $class }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" id="resultsUploadGroup">
                <label>Term Results from Previous School</label>
                <input type="file" name="results_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                <p style="font-size:11px; color:var(--muted); margin-top:4px;">
                    Upload your term results (PDF, DOC, DOCX, JPG, PNG). Max 5MB.
                    <span style="color:var(--red-ghana); font-weight:600;" id="resultsRequiredMsg">Required for Primary 1 to JHS 2.</span>
                </p>
            </div>

            <button type="submit" class="btn-primary">Submit Enrollment Application →</button>
        </form>
    </div>

    <p style="text-align:center; margin-top:20px; font-size:11px; color:var(--muted);">
        {{ $currentSchool->name ?? 'Your School' }}
    </p>
@endsection

@push('scripts')
<script>
    // Unchanged from the original - client-side JS behaves identically in Blade.
    document.getElementById('classSelect').addEventListener('change', function() {
        const classVal = this.value;
        const requiredClasses = ['Primary 1','Primary 2','Primary 3','Primary 4','Primary 5','Primary 6','JHS 1','JHS 2'];
        const fileInput = document.querySelector('input[name="results_file"]');
        const msg = document.getElementById('resultsRequiredMsg');
        if (requiredClasses.includes(classVal)) {
            fileInput.setAttribute('required', 'required');
            msg.style.display = 'inline';
        } else {
            fileInput.removeAttribute('required');
            msg.style.display = 'none';
        }
    });
    window.addEventListener('DOMContentLoaded', function() {
        document.getElementById('classSelect').dispatchEvent(new Event('change'));
    });
</script>
@endpush
