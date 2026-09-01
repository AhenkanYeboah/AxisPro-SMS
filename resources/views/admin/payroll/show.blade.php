@extends('layouts.dashboard')

@section('title', 'Payroll — ' . $run->periodLabel())
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Payroll')
@section('welcome-message', $run->periodLabel())

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
    @if (session('status'))
        <div class="message success">✅ {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="message error">❌ {{ $errors->first() }}</div>
    @endif

    <div class="card card-padded" style="margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            @php
                $tone = match ($run->status) {
                    'approved', 'paid' => 'status-active',
                    'cancelled' => 'status-declined',
                    default => 'status-pending',
                };
            @endphp
            <h3 style="margin:0;">{{ $run->periodLabel() }} <span class="status-badge {{ $tone }}">{{ ucfirst(str_replace('_', ' ', $run->status)) }}</span></h3>
            <p style="font-size:13px; color:var(--muted); margin:4px 0 0;">{{ $run->payslips->count() }} staff on this run.</p>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="{{ route('admin.payroll.export', $run) }}" class="btn-submit" style="width:auto; padding:8px 16px; text-decoration:none; display:inline-block; background:transparent; color:inherit; border:1px solid var(--border, #d1d5db);">Export bank CSV</a>
            @if ($run->status === 'pending_approval')
                <form method="POST" action="{{ route('admin.payroll.approve', $run) }}">
                    @csrf
                    <button type="submit" class="btn-submit" style="width:auto; padding:8px 16px;">Approve</button>
                </form>
            @endif
            @if ($run->status === 'approved')
                <form method="POST" action="{{ route('admin.payroll.paid', $run) }}">
                    @csrf
                    <button type="submit" class="btn-submit" style="width:auto; padding:8px 16px;">Mark as paid</button>
                </form>
            @endif
        </div>
    </div>

    <div class="stats-grid" style="margin-bottom:24px;">
        <div class="stat-card"><div class="stat-info"><div class="stat-number">GHS {{ number_format($run->total_gross_pesewas / 100, 2) }}</div><div class="stat-label">Gross</div></div></div>
        <div class="stat-card"><div class="stat-info"><div class="stat-number">GHS {{ number_format($run->total_ssnit_employee_pesewas / 100, 2) }}</div><div class="stat-label">SSNIT (employee)</div></div></div>
        <div class="stat-card"><div class="stat-info"><div class="stat-number">GHS {{ number_format($run->total_paye_pesewas / 100, 2) }}</div><div class="stat-label">PAYE</div></div></div>
        <div class="stat-card"><div class="stat-info"><div class="stat-number">GHS {{ number_format($run->total_net_pesewas / 100, 2) }}</div><div class="stat-label">Net pay</div></div></div>
    </div>

    <div class="card card-padded">
        <table>
            <thead>
                <tr>
                    <th>Staff</th>
                    <th>Basic</th>
                    <th>Allow.</th>
                    <th>Gross</th>
                    <th>SSNIT</th>
                    <th>PAYE</th>
                    <th>Net</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($run->payslips as $payslip)
                    <tr>
                        <td>
                            <div>{{ $payslip->staff->full_name }}</div>
                            <div style="font-size:12px; color:var(--muted);">{{ $payslip->staff->staff_no }}</div>
                        </td>
                        <td>{{ number_format($payslip->basic_salary_pesewas / 100, 2) }}</td>
                        <td>{{ number_format($payslip->allowances_pesewas / 100, 2) }}</td>
                        <td>{{ number_format($payslip->gross_pay_pesewas / 100, 2) }}</td>
                        <td>{{ number_format($payslip->ssnit_employee_pesewas / 100, 2) }}</td>
                        <td>{{ number_format($payslip->paye_pesewas / 100, 2) }}</td>
                        <td><strong>{{ number_format($payslip->net_pay_pesewas / 100, 2) }}</strong></td>
                        <td><a href="{{ route('admin.payslips.show', $payslip) }}">Payslip →</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
