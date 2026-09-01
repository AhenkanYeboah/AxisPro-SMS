@extends('layouts.dashboard')

@section('title', 'Payroll — Add Staff')
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Payroll')
@section('welcome-message', 'Add Staff')

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('admin.dashboard') }}"><i class="nav-icon">▤</i> Dashboard</a>
    <a href="{{ route('admin.staff.index') }}" class="active"><i class="nav-icon">🧾</i> Staff</a>
    <a href="{{ route('admin.payroll.index') }}"><i class="nav-icon">💰</i> Payroll</a>
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

    <div class="card card-padded" style="max-width:720px;">
        <h3 style="margin-top:0;">Add a staff member</h3>
        <p style="font-size:13px; color:var(--muted);">
            Covers teaching and non-teaching staff. Basic salary and allowances feed straight into payroll runs.
        </p>

        <form method="POST" action="{{ route('admin.staff.store') }}">
            @csrf
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <div class="form-group" style="flex:1; min-width:200px;">
                    <label>Staff No.</label>
                    <input type="text" name="staff_no" value="{{ old('staff_no') }}" placeholder="e.g. STF-001" required>
                </div>
                <div class="form-group" style="flex:2; min-width:240px;">
                    <label>Full name</label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" required>
                </div>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <div class="form-group" style="flex:1; min-width:200px;">
                    <label>Position</label>
                    <input type="text" name="position" value="{{ old('position') }}" placeholder="e.g. Mathematics Teacher, Accountant, Security" required>
                </div>
                <div class="form-group" style="flex:1; min-width:200px;">
                    <label>Department (optional)</label>
                    <input type="text" name="department" value="{{ old('department') }}">
                </div>
                <div class="form-group" style="width:180px;">
                    <label>Employment type</label>
                    <select name="employment_type" required>
                        <option value="full_time">Full-time</option>
                        <option value="part_time">Part-time</option>
                        <option value="contract">Contract</option>
                        <option value="casual">Casual</option>
                    </select>
                </div>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <div class="form-group" style="flex:1; min-width:160px;">
                    <label>Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}">
                </div>
                <div class="form-group" style="flex:1; min-width:200px;">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email') }}">
                </div>
                <div class="form-group" style="width:160px;">
                    <label>Date joined</label>
                    <input type="date" name="date_joined" value="{{ old('date_joined') }}">
                </div>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <div class="form-group" style="flex:1; min-width:160px;">
                    <label>Bank name</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name') }}">
                </div>
                <div class="form-group" style="flex:1; min-width:160px;">
                    <label>Account number</label>
                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}">
                </div>
                <div class="form-group" style="flex:1; min-width:200px;">
                    <label>Account name</label>
                    <input type="text" name="bank_account_name" value="{{ old('bank_account_name') }}">
                </div>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <div class="form-group" style="flex:1; min-width:160px;">
                    <label>Mobile money provider</label>
                    <input type="text" name="mobile_money_provider" value="{{ old('mobile_money_provider') }}" placeholder="MTN, Telecel, AT">
                </div>
                <div class="form-group" style="flex:1; min-width:160px;">
                    <label>Mobile money number</label>
                    <input type="text" name="mobile_money_number" value="{{ old('mobile_money_number') }}">
                </div>
            </div>

            <hr style="margin:20px 0; border:none; border-top:1px solid var(--border, #e5e7eb);">

            <div class="form-group">
                <label>Basic salary (GHS / month)</label>
                <input type="number" step="0.01" min="0" name="basic_ghs" value="{{ old('basic_ghs') }}" required style="max-width:200px;">
            </div>

            <div class="form-group">
                <label>Allowances (optional)</label>
                <div style="display:flex; gap:8px; margin-bottom:8px;">
                    <input type="text" name="allowances[0][name]" placeholder="e.g. Housing Allowance" style="flex:1;">
                    <input type="number" step="0.01" min="0" name="allowances[0][amount_ghs]" placeholder="GHS" style="width:140px;">
                </div>
                <div style="display:flex; gap:8px;">
                    <input type="text" name="allowances[1][name]" placeholder="e.g. Transport Allowance" style="flex:1;">
                    <input type="number" step="0.01" min="0" name="allowances[1][amount_ghs]" placeholder="GHS" style="width:140px;">
                </div>
                <p style="font-size:12px; color:var(--muted); margin-top:6px;">
                    SSNIT and PAYE are calculated automatically at payroll time — don't add them here.
                </p>
            </div>

            <button type="submit" class="btn-submit" style="width:auto; padding:10px 28px; margin-top:12px;">Save staff member</button>
        </form>
    </div>
@endsection
