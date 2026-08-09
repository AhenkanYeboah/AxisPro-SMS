@extends('layouts.dashboard')

@section('title', 'Admin Dashboard')
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Admin Dashboard')
@section('welcome-message', 'Welcome, ' . auth('admin')->user()->full_name . ' 👋')

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.form') }}"><i class="nav-icon">✎</i> Enrollment Form</a>
    <a href="{{ route('admin.dashboard') }}" class="active"><i class="nav-icon">▤</i> Dashboard</a>
    <a href="{{ route('admin.exams.index') }}"><i class="nav-icon">📝</i> Entrance Exams</a>
    <a href="{{ route('admin.invites.index') }}"><i class="nav-icon">✉</i> Invites</a>
    <a href="{{ route('admin.fees.index') }}"><i class="nav-icon">💳</i> Billing</a>
    <a href="{{ route('admin.notices.index') }}"><i class="nav-icon">📣</i> Notices</a>
    <a href="{{ route('admin.class-levels.index') }}"><i class="nav-icon">🏫</i> Classes</a>
    <a href="{{ route('admin.teachers.index') }}"><i class="nav-icon">🧑‍🏫</i> Teachers</a>
    <a href="{{ route('admin.exemplars.index') }}"><i class="nav-icon">⭐</i> Exemplars</a>
    <a href="{{ route('admin.settings') }}"><i class="nav-icon">🎨</i> Settings</a>
@endsection

@section('topbar-right')
    <span class="user-greeting">🔑 <strong>{{ auth('admin')->user()->username }}</strong></span>
    <a href="{{ route('student.form') }}" class="btn-gold" style="padding:8px 18px; font-size:12px;">+ New Enrollment</a>
    <form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="auth-btn auth-btn-logout">🚪 Logout</button>
    </form>
@endsection

@section('content')
    @if (session('status'))
        <div class="message success">✅ {{ session('status') }}</div>
    @endif

    {{-- STATS --}}
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon total">🎓</div><div class="stat-info"><div class="stat-number">{{ $stats['total'] }}</div><div class="stat-label">Total Applicants</div></div></div>
        <div class="stat-card"><div class="stat-icon admitted">✅</div><div class="stat-info"><div class="stat-number">{{ $stats['admitted'] }}</div><div class="stat-label">Admitted</div></div></div>
        <div class="stat-card"><div class="stat-icon pending">⏳</div><div class="stat-info"><div class="stat-number">{{ $stats['pending'] }}</div><div class="stat-label">Pending Review</div></div></div>
        <div class="stat-card"><div class="stat-icon male">👨</div><div class="stat-info"><div class="stat-number">{{ $stats['male'] }}</div><div class="stat-label">Male</div></div></div>
        <div class="stat-card"><div class="stat-icon female">👩</div><div class="stat-info"><div class="stat-number">{{ $stats['female'] }}</div><div class="stat-label">Female</div></div></div>
    </div>

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('admin.dashboard') }}" class="filters">
        <div class="filter-group">
            <label>Search by Name</label>
            <input type="text" name="name" placeholder="Enter name..." value="{{ request('name') }}">
        </div>
        <div class="filter-group">
            <label>Admission Status</label>
            <select name="admission_status">
                <option value="">All Statuses</option>
                <option value="admitted" {{ request('admission_status') == 'admitted' ? 'selected' : '' }}>Admitted</option>
                <option value="undecided" {{ request('admission_status') == 'undecided' ? 'selected' : '' }}>Pending</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Gender</label>
            <select name="gender">
                <option value="">All Genders</option>
                <option value="Male" {{ request('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                <option value="Female" {{ request('gender') == 'Female' ? 'selected' : '' }}>Female</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Class</label>
            <select name="class">
                <option value="">All Classes</option>
                @foreach (['Creche','Nursery','Kindergarten','Primary 1','Primary 2','Primary 3','Primary 4','Primary 5','Primary 6','JHS 1','JHS 2','JHS 3'] as $cls)
                    <option value="{{ $cls }}" {{ request('class') == $cls ? 'selected' : '' }}>{{ $cls }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn-filter">Apply Filters</button>
            <a href="{{ route('admin.dashboard') }}" class="btn-reset">↺ Reset</a>
        </div>
    </form>

    {{-- APPLICANTS TABLE --}}
    <div class="card table-scroll" style="overflow:hidden; overflow-x:auto;">
        <table style="min-width:1500px;">
            <thead>
                <tr>
                    <th>#</th><th>Photo</th><th>Student ID</th><th>Full Name</th><th>Email</th>
                    <th>Class</th><th>Exam Date</th><th>Exam Done</th><th>Verified</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $i => $student)
                    <tr>
                        <td style="color:var(--muted); font-size:12px;">{{ $i + 1 }}</td>
                        <td>
                            @if ($student->profile_image)
                                <img src="{{ asset('storage/' . $student->profile_image) }}" style="width:36px; height:36px; border-radius:50%; object-fit:cover;" alt="">
                            @else
                                <span style="font-size:18px;">👤</span>
                            @endif
                        </td>
                        <td><strong>{{ $student->student_id ?: '—' }}</strong></td>
                        <td style="font-weight:600; white-space:nowrap;">{{ $student->fullName() }}</td>
                        <td>{{ $student->email }}</td>
                        <td>{{ $student->class ?: '—' }}</td>
                        <td>{{ $student->exam_date?->format('M d, Y') ?? '—' }}</td>
                        <td>{{ $student->exam_completed ? '✅' : '❌' }}</td>
                        <td>{{ $student->exam_verified ? '✅' : '❌' }}</td>
                        <td><span class="status-badge status-{{ $student->status }}">{{ ucfirst($student->status) }}</span></td>
                        <td style="white-space:nowrap;">
                            <a href="{{ route('admin.students.show', $student) }}" class="view-btn">View</a>
                            <form method="POST" action="{{ route('admin.students.destroy', $student) }}" style="display:inline;" onsubmit="return confirm('Delete this applicant?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="delete-btn">✕</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" style="text-align:center; padding:40px; color:var(--muted);">No records found matching your filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- CLASS ROSTER --}}
    <div class="card card-padded" style="margin:24px 0;">
        <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">🎓 Class Roster (Admitted Students)</h4>
        @forelse ($classRoster as $className => $studentsInClass)
            <div style="margin-bottom:20px;">
                <h5 style="font-size:13px; color:var(--muted); margin-bottom:8px;">{{ $className }} ({{ $studentsInClass->count() }})</h5>
                <table>
                    <thead><tr><th>Student ID</th><th>Full Name</th><th>Email</th><th>Gender</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($studentsInClass as $student)
                            <tr>
                                <td><strong>{{ $student->student_id }}</strong></td>
                                <td class="student-name">{{ $student->fullName() }}</td>
                                <td class="student-email">{{ $student->email }}</td>
                                <td>{{ $student->gender ?: '—' }}</td>
                                <td><span class="status-badge status-active">Active</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <p style="color:var(--muted);">No admitted students yet.</p>
        @endforelse
    </div>

    {{-- TOP REGIONS --}}
    <div class="card card-padded" style="margin-bottom:24px;">
        <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:12px;">📍 Top Regions</h4>
        @forelse ($stats['by_region'] as $region => $count)
            <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--border); font-size:13px;">
                <span>{{ $region }}</span>
                <span style="font-weight:600; color:var(--green-deep);">{{ $count }}</span>
            </div>
        @empty
            <p style="color:var(--muted); font-size:13px;">No data available.</p>
        @endforelse
    </div>

    {{-- RECENT APPLICANTS --}}
    <div class="card card-padded" style="margin-bottom:24px;">
        <h4 style="font-size:14px; font-weight:600; color:var(--green-deep); margin-bottom:12px;">🕐 Recent Applicants</h4>
        @if ($recentApplicants->isNotEmpty())
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                @foreach ($recentApplicants as $app)
                    <div style="display:flex; align-items:center; gap:12px; padding:10px 14px; background:var(--cream); border-radius:8px; border:1px solid var(--border);">
                        @if ($app->profile_image)
                            <img src="{{ asset('storage/' . $app->profile_image) }}" style="width:40px; height:40px; border-radius:50%; object-fit:cover;" alt="">
                        @else
                            <span style="font-size:22px;">👤</span>
                        @endif
                        <div style="flex:1;">
                            <div style="font-weight:600; font-size:13px;">{{ $app->first_name }} {{ $app->last_name }}</div>
                            <div style="font-size:11px; color:var(--muted);">{{ $app->email }}</div>
                        </div>
                        <span class="status-badge status-{{ $app->admission_status }}">{{ ucfirst($app->admission_status) }}</span>
                        <a href="{{ route('admin.students.show', $app) }}" class="view-btn" style="font-size:10px;">View</a>
                    </div>
                @endforeach
            </div>
        @else
            <p style="color:var(--muted);">No applicants yet.</p>
        @endif
    </div>

    {{-- ACTIVITIES MANAGER --}}
    <div class="card card-padded" style="margin-bottom:24px;">
        <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">📋 School Activities Manager</h4>

        <form method="POST" action="{{ route('activities.store') }}" style="display:grid; grid-template-columns: 2fr 1fr 1fr auto; gap:12px; align-items:end; margin-bottom:20px;">
            @csrf
            <div>
                <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Title *</label>
                <input type="text" name="title" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
            </div>
            <div>
                <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Date *</label>
                <input type="date" name="activity_date" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
            </div>
            <div>
                <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Category</label>
                <input type="text" name="category" placeholder="General" style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
            </div>
            <button type="submit" class="btn-filter">➕ Add</button>
        </form>

        <table>
            <thead><tr><th>Title</th><th>Category</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($allActivities as $activity)
                    <tr>
                        <td><strong>{{ $activity->title }}</strong></td>
                        <td>{{ $activity->category }}</td>
                        <td>{{ $activity->activity_date->format('M d, Y') }}</td>
                        <td>
                            <form method="POST" action="{{ route('activities.destroy', $activity) }}" style="display:inline;" onsubmit="return confirm('Delete this activity?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="delete-btn">🗑 Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center; color:var(--muted); padding:20px;">No activities yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
