@extends('layouts.dashboard')

@section('title', 'New Payroll Run')
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Payroll')
@section('welcome-message', 'New Payroll Run')

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('admin.dashboard') }}"><i class="nav-icon">▤</i> Dashboard</a>
    <a href="{{ route('admin.staff.index') }}"><i class="nav-icon">🧾</i> Staff</a>
    <a href="{{ route('admin.payroll.index') }}" class="active"><i class="nav-icon">💰</i> Payroll</a>
@endsection

@section('topbar-right')
    <span class="user-greeting">🔑 <strong>{{ auth('admin')->user()->username }}</strong></span>
    <form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="auth-btn auth-btn-logout">🚪 Logout</button>
    </form>
@endsection

@section('content')
    @if ($errors->any())
        <div class="message error">❌ {{ $errors->first() }}</div>
    @endif

    <div class="card card-padded" style="max-width:520px;">
        <h3 style="margin-top:0;">Generate a payroll run</h3>
        <p style="font-size:13px; color:var(--muted);">
            Will calculate pay for <strong>{{ $activeStaffCount }}</strong> active staff member{{ $activeStaffCount === 1 ? '' : 's' }},
            using each person's current salary structure and allowances, plus SSNIT and PAYE.
        </p>

        <form method="POST" action="{{ route('admin.payroll.store') }}">
            @csrf
            <div style="display:flex; gap:12px;">
                <div class="form-group" style="flex:1;">
                    <label>Month</label>
                    <select name="period_month" required>
                        @foreach (['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $label)
                            <option value="{{ $i + 1 }}" @selected(old('period_month', now()->month) == $i + 1)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="width:140px;">
                    <label>Year</label>
                    <input type="number" name="period_year" min="2020" max="2035" value="{{ old('period_year', now()->year) }}" required>
                </div>
            </div>

            <div class="card" style="background:var(--surface-subtle, #f9fafb); padding:12px; font-size:12px; color:var(--muted); margin:16px 0;">
                For each staff member: basic salary + allowances = gross pay. SSNIT (employee share) is deducted, then PAYE
                is calculated on the remainder using the current tax bands. Net pay = gross − SSNIT − PAYE − other deductions.
                The run is created for review — nothing is marked paid until you approve it.
            </div>

            <button type="submit" class="btn-submit" style="width:auto; padding:10px 28px;">Generate payroll run →</button>
        </form>
    </div>
@endsection
