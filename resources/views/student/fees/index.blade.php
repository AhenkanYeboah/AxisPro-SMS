@extends('layouts.dashboard')

@section('title', 'My Fees')
@section('sidebar-sub', 'Student Dashboard')
@section('page-label', 'My Fees')
@section('welcome-message', 'Fees & Payments')

@section('nav-links')
    <a href="{{ route('student.dashboard') }}"><i class="nav-icon">⌂</i> Dashboard</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.assignments') }}"><i class="nav-icon">📝</i> Assignments</a>
    <a href="{{ route('student.results') }}"><i class="nav-icon">📊</i> My Results</a>
    <a href="{{ route('student.timetable') }}"><i class="nav-icon">🗓</i> Timetable</a>
    <a href="{{ route('student.report-card') }}"><i class="nav-icon">📊</i> Report Card</a>
    <a href="{{ route('student.fees') }}" class="active"><i class="nav-icon">💳</i> My Fees</a>
    <a href="{{ route('student.virtual-classes') }}"><i class="nav-icon">🎥</i> Virtual Classes</a>
    <form method="POST" action="{{ route('student.logout') }}" style="display:inline;">
        @csrf
        <a href="#" onclick="this.closest('form').submit(); return false;"><i class="nav-icon">🚪</i> Logout</a>
    </form>
@endsection

@section('content')
    <div class="stats-grid" style="margin-bottom:24px;">
        <div class="stat-card"><div class="stat-icon total"><div class="stat-info"><div class="stat-number">GHS {{ number_format($totals['billed'] / 100, 2) }}</div><div class="stat-label">Total Billed</div></div></div></div>
        <div class="stat-card"><div class="stat-icon admitted"><div class="stat-info"><div class="stat-number">GHS {{ number_format($totals['paid'] / 100, 2) }}</div><div class="stat-label">Total Paid</div></div></div></div>
        <div class="stat-card"><div class="stat-icon {{ $totals['balance'] > 0 ? 'pending' : 'admitted' }}"><div class="stat-info"><div class="stat-number">GHS {{ number_format($totals['balance'] / 100, 2) }}</div><div class="stat-label">Outstanding Balance</div></div></div></div>
    </div>

    <div class="card card-padded">
        <h3 style="margin-top:0;">Fee history</h3>
        <p style="font-size:13px; color:var(--muted); margin-top:-6px;">
            This is a record of fees billed to you and payments received by the school office.
            To make a payment, please visit the school's finance office.
        </p>
        <table>
            <thead>
                <tr><th>Fee</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th><th>Due</th></tr>
            </thead>
            <tbody>
                @forelse ($studentFees as $fee)
                    <tr>
                        <td>{{ $fee->feeItem->name }}</td>
                        <td>GHS {{ number_format($fee->amount_pesewas / 100, 2) }}</td>
                        <td>GHS {{ number_format($fee->amountPaidPesewas() / 100, 2) }}</td>
                        <td>GHS {{ number_format($fee->balancePesewas() / 100, 2) }}</td>
                        <td>
                            @if ($fee->status === 'paid')
                                <span style="color:#2E7D32;">Paid</span>
                            @elseif ($fee->status === 'partially_paid')
                                <span style="color:#B8860B;">Partially Paid</span>
                            @elseif ($fee->status === 'waived')
                                <span style="color:var(--muted);">Waived</span>
                            @else
                                <span style="color:#B23B3B;">Unpaid</span>
                            @endif
                        </td>
                        <td>{{ $fee->due_date?->format('d M Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center; color:var(--muted);">No fees have been billed to you yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
