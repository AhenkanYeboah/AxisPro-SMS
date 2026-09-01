@extends('layouts.dashboard')

@section('title', 'Payroll — Staff')
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Payroll')
@section('welcome-message', 'Staff')

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('admin.dashboard') }}"><i class="nav-icon">▤</i> Dashboard</a>
    <a href="{{ route('admin.fees.index') }}"><i class="nav-icon">💳</i> Billing</a>
    <a href="{{ route('admin.teachers.index') }}"><i class="nav-icon">🧑‍🏫</i> Teachers</a>
    <a href="{{ route('admin.staff.index') }}" class="active"><i class="nav-icon">🧾</i> Staff</a>
    <a href="{{ route('admin.payroll.index') }}"><i class="nav-icon">💰</i> Payroll</a>
    <a href="{{ route('admin.settings') }}"><i class="nav-icon">🎨</i> Settings</a>
@endsection

@section('topbar-right')
    <span class="user-greeting">🔑 <strong>{{ auth('admin')->user()->username }}</strong></span>
    <form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="auth-btn auth-btn-logout">🚪 Logout</button>
    </form>
@endsection

@section('content')
    @if (session('status'))
        <div class="message success">✅ {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="message error">❌ {{ $errors->first() }}</div>
    @endif

    <div class="card card-padded" style="margin-bottom:24px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h3 style="margin:0;">All staff</h3>
            <p style="font-size:13px; color:var(--muted); margin:4px 0 0;">Teaching and non-teaching staff, for payroll purposes.</p>
        </div>
        <a href="{{ route('admin.staff.create') }}" class="btn-submit" style="width:auto; padding:10px 20px; text-decoration:none; display:inline-block;">+ Add staff</a>
    </div>

    <div class="card card-padded">
        <table>
            <thead>
                <tr>
                    <th>Staff No.</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Type</th>
                    <th>Basic (GHS)</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($staff as $s)
                    <tr>
                        <td>{{ $s->staff_no }}</td>
                        <td>{{ $s->full_name }}</td>
                        <td>{{ $s->position }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $s->employment_type)) }}</td>
                        <td>{{ number_format(($s->currentSalaryStructure?->basic_salary_pesewas ?? 0) / 100, 2) }}</td>
                        <td>
                            @if ($s->is_active)
                                <span class="status-badge status-active">Active</span>
                            @else
                                <span class="status-badge status-declined">Inactive</span>
                            @endif
                        </td>
                        <td><a href="{{ route('admin.staff.edit', $s) }}">Edit</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; color:var(--muted); padding:24px;">
                            No staff yet — add your first staff member to start running payroll.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
