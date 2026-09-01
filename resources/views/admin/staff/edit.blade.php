@extends('layouts.dashboard')

@section('title', 'Payroll — Edit Staff')
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Payroll')
@section('welcome-message', 'Edit ' . $staff->full_name)

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
        <h3 style="margin-top:0;">{{ $staff->full_name }} <span style="font-size:13px; color:var(--muted); font-weight:normal;">({{ $staff->staff_no }})</span></h3>

        <form method="POST" action="{{ route('admin.staff.update', $staff) }}">
            @csrf
            @method('PUT')

            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <div class="form-group" style="flex:2; min-width:240px;">
                    <label>Full name</label>
                    <input type="text" name="full_name" value="{{ old('full_name', $staff->full_name) }}" required>
                </div>
                <div class="form-group" style="width:180px;">
                    <label>Status</label>
                    <select name="is_active" required>
                        <option value="1" @selected(old('is_active', (int) $staff->is_active) == 1)>Active</option>
                        <option value="0" @selected(old('is_active', (int) $staff->is_active) == 0)>Inactive</option>
                    </select>
                </div>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <div class="form-group" style="flex:1; min-width:200px;">
                    <label>Position</label>
                    <input type="text" name="position" value="{{ old('position', $staff->position) }}" required>
                </div>
                <div class="form-group" style="flex:1; min-width:200px;">
                    <label>Department (optional)</label>
                    <input type="text" name="department" value="{{ old('department', $staff->department) }}">
                </div>
                <div class="form-group" style="width:180px;">
                    <label>Employment type</label>
                    <select name="employment_type" required>
                        @foreach (['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract', 'casual' => 'Casual'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('employment_type', $staff->employment_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <div class="form-group" style="flex:1; min-width:160px;">
                    <label>Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $staff->phone) }}">
                </div>
                <div class="form-group" style="flex:1; min-width:200px;">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email', $staff->email) }}">
                </div>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <div class="form-group" style="flex:1; min-width:160px;">
                    <label>Bank name</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name', $staff->bank_name) }}">
                </div>
                <div class="form-group" style="flex:1; min-width:160px;">
                    <label>Account number</label>
                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $staff->bank_account_number) }}">
                </div>
                <div class="form-group" style="flex:1; min-width:200px;">
                    <label>Account name</label>
                    <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $staff->bank_account_name) }}">
                </div>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <div class="form-group" style="flex:1; min-width:160px;">
                    <label>Mobile money provider</label>
                    <input type="text" name="mobile_money_provider" value="{{ old('mobile_money_provider', $staff->mobile_money_provider) }}">
                </div>
                <div class="form-group" style="flex:1; min-width:160px;">
                    <label>Mobile money number</label>
                    <input type="text" name="mobile_money_number" value="{{ old('mobile_money_number', $staff->mobile_money_number) }}">
                </div>
            </div>

            <hr style="margin:20px 0; border:none; border-top:1px solid var(--border, #e5e7eb);">

            <div class="form-group">
                <label>Basic salary (GHS / month)</label>
                <input type="number" step="0.01" min="0" name="basic_ghs"
                    value="{{ old('basic_ghs', ($staff->currentSalaryStructure?->basic_salary_pesewas ?? 0) / 100) }}"
                    required style="max-width:200px;">
                <p style="font-size:12px; color:var(--muted); margin-top:4px;">
                    Changing this creates a new salary record dated today — the old one is kept for history.
                </p>
            </div>

            <div class="form-group">
                <label>Allowances</label>
                @php $existingAllowances = $allowances->values(); @endphp
                <div id="allowance-rows">
                    @forelse ($existingAllowances as $i => $item)
                        <div style="display:flex; gap:8px; margin-bottom:8px;">
                            <input type="text" name="allowances[{{ $i }}][name]" value="{{ $item->name }}" style="flex:1;">
                            <input type="number" step="0.01" min="0" name="allowances[{{ $i }}][amount_ghs]" value="{{ $item->amount_pesewas / 100 }}" style="width:140px;">
                        </div>
                    @empty
                        <div style="display:flex; gap:8px; margin-bottom:8px;">
                            <input type="text" name="allowances[0][name]" placeholder="e.g. Housing Allowance" style="flex:1;">
                            <input type="number" step="0.01" min="0" name="allowances[0][amount_ghs]" placeholder="GHS" style="width:140px;">
                        </div>
                    @endforelse
                </div>
                <p style="font-size:12px; color:var(--muted); margin-top:6px;">
                    Saving replaces the allowance list above. Leave a row blank to drop it.
                    Statutory deductions (SSNIT, PAYE) aren't listed here — they're calculated automatically.
                </p>
            </div>

            <button type="submit" class="btn-submit" style="width:auto; padding:10px 28px; margin-top:12px;">Save changes</button>
        </form>
    </div>
@endsection
