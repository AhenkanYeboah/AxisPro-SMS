@extends('layouts.dashboard')

@section('title', 'Payslip — ' . $payslip->staff->full_name)
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Payroll')
@section('welcome-message', 'Payslip')

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
    <div class="card card-padded" style="max-width:640px; margin:0 auto;">
        <div style="text-align:center; border-bottom:1px solid var(--border, #e5e7eb); padding-bottom:16px; margin-bottom:16px;">
            <h3 style="margin:0;">{{ $payslip->staff->school->name ?? 'Payslip' }}</h3>
            <p style="font-size:13px; color:var(--muted); margin:4px 0 0;">{{ $payslip->payrollRun->periodLabel() }}</p>
        </div>

        <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:16px; flex-wrap:wrap; gap:12px;">
            <div>
                <strong>{{ $payslip->staff->full_name }}</strong><br>
                {{ $payslip->staff->staff_no }} · {{ $payslip->staff->position }}
            </div>
            <div>
                {{ $payslip->staff->bank_name }}<br>
                {{ $payslip->staff->bank_account_number }}
            </div>
        </div>

        <table>
            <tbody>
                <tr><td colspan="2"><strong>Earnings</strong></td></tr>
                <tr><td>Basic salary</td><td style="text-align:right;">{{ number_format($payslip->basic_salary_pesewas / 100, 2) }}</td></tr>
                <tr><td>Allowances</td><td style="text-align:right;">{{ number_format($payslip->allowances_pesewas / 100, 2) }}</td></tr>
                <tr><td><strong>Gross pay</strong></td><td style="text-align:right;"><strong>{{ number_format($payslip->gross_pay_pesewas / 100, 2) }}</strong></td></tr>
                <tr><td colspan="2" style="padding-top:12px;"><strong>Deductions</strong></td></tr>
                <tr><td>SSNIT (employee, 5.5%)</td><td style="text-align:right;">-{{ number_format($payslip->ssnit_employee_pesewas / 100, 2) }}</td></tr>
                <tr><td>PAYE</td><td style="text-align:right;">-{{ number_format($payslip->paye_pesewas / 100, 2) }}</td></tr>
                <tr><td>Other deductions</td><td style="text-align:right;">-{{ number_format($payslip->other_deductions_pesewas / 100, 2) }}</td></tr>
                <tr><td style="padding-top:12px;"><strong>Net pay</strong></td><td style="text-align:right; padding-top:12px;"><strong>GHS {{ number_format($payslip->net_pay_pesewas / 100, 2) }}</strong></td></tr>
            </tbody>
        </table>

        <p style="font-size:12px; color:var(--muted); margin-top:16px;">
            Employer SSNIT contribution (13.5%): GHS {{ number_format($payslip->ssnit_employer_pesewas / 100, 2) }} — employer cost, not deducted from staff pay.
        </p>

        <div style="text-align:center; margin-top:20px;">
            <button onclick="window.print()" class="btn-submit" style="width:auto; padding:8px 24px;">Print</button>
            <a href="{{ route('admin.payroll.show', $payslip->payrollRun) }}" style="margin-left:12px; font-size:13px;">Back to run</a>
        </div>
    </div>
@endsection
