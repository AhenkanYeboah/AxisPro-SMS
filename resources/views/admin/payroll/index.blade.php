@extends('layouts.dashboard')

@section('title', 'Payroll Runs')
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Payroll')
@section('welcome-message', 'Payroll Runs')

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

    <div class="card card-padded" style="margin-bottom:24px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h3 style="margin:0;">Payroll runs</h3>
            <p style="font-size:13px; color:var(--muted); margin:4px 0 0;">SSNIT + PAYE calculated automatically for all active staff.</p>
        </div>
        <a href="{{ route('admin.payroll.create') }}" class="btn-submit" style="width:auto; padding:10px 20px; text-decoration:none; display:inline-block;">+ New payroll run</a>
    </div>

    <div class="card card-padded">
        <table>
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Staff</th>
                    <th>Gross</th>
                    <th>Net</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($runs as $run)
                    <tr>
                        <td>{{ $run->periodLabel() }}</td>
                        <td>{{ $run->payslips_count }}</td>
                        <td>GHS {{ number_format($run->total_gross_pesewas / 100, 2) }}</td>
                        <td>GHS {{ number_format($run->total_net_pesewas / 100, 2) }}</td>
                        <td>
                            @php
                                $tone = match ($run->status) {
                                    'approved', 'paid' => 'status-active',
                                    'cancelled' => 'status-declined',
                                    default => 'status-pending',
                                };
                            @endphp
                            <span class="status-badge {{ $tone }}">{{ ucfirst(str_replace('_', ' ', $run->status)) }}</span>
                        </td>
                        <td><a href="{{ route('admin.payroll.show', $run) }}">View →</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; color:var(--muted); padding:24px;">
                            No payroll runs yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
