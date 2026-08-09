@extends('layouts.dashboard')

@section('title', $studentFee->student->fullName() . ' — ' . $studentFee->feeItem->name)
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Billing')
@section('welcome-message', $studentFee->student->fullName() . ' — ' . $studentFee->feeItem->name)

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.form') }}"><i class="nav-icon">✎</i> Enrollment Form</a>
    <a href="{{ route('admin.dashboard') }}"><i class="nav-icon">▤</i> Dashboard</a>
    <a href="{{ route('admin.exams.index') }}"><i class="nav-icon">📝</i> Entrance Exams</a>
    <a href="{{ route('admin.invites.index') }}"><i class="nav-icon">✉</i> Invites</a>
    <a href="{{ route('admin.fees.index') }}" class="active"><i class="nav-icon">💳</i> Billing</a>
    <a href="{{ route('admin.notices.index') }}"><i class="nav-icon">📣</i> Notices</a>
    <a href="{{ route('admin.class-levels.index') }}"><i class="nav-icon">🏫</i> Classes</a>
    <a href="{{ route('admin.teachers.index') }}"><i class="nav-icon">🧑‍🏫</i> Teachers</a>
    <a href="{{ route('admin.exemplars.index') }}"><i class="nav-icon">⭐</i> Exemplars</a>
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

    <p style="margin-top:-8px; margin-bottom:20px;">
        <a href="{{ route('admin.fees.index') }}" style="font-size:13px; color:var(--muted);">← Back to Billing</a>
    </p>

    <div class="card card-padded" style="margin-bottom:24px;">
        <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:16px;">
            <div>
                <h3 style="margin:0 0 4px 0;">{{ $studentFee->student->fullName() }}</h3>
                <p style="color:var(--muted); font-size:13px; margin:0;">{{ $studentFee->student->class }} · {{ $studentFee->feeItem->name }}</p>
            </div>
            @if ($studentFee->status !== 'waived' && $studentFee->status !== 'paid')
                <form method="POST" action="{{ route('admin.student-fees.waive', $studentFee) }}" onsubmit="return confirm('Waive the remaining balance for this fee?');">
                    @csrf
                    <button type="submit" class="auth-btn" style="padding:6px 14px; font-size:12px;">Waive Balance</button>
                </form>
            @endif
        </div>

        <div style="display:flex; gap:32px; margin-top:20px; flex-wrap:wrap;">
            <div>
                <div style="font-size:12px; color:var(--muted);">Billed</div>
                <div style="font-size:20px; font-weight:700;">GHS {{ number_format($studentFee->amount_pesewas / 100, 2) }}</div>
            </div>
            <div>
                <div style="font-size:12px; color:var(--muted);">Paid</div>
                <div style="font-size:20px; font-weight:700; color:#2E7D32;">GHS {{ number_format($studentFee->amountPaidPesewas() / 100, 2) }}</div>
            </div>
            <div>
                <div style="font-size:12px; color:var(--muted);">Balance</div>
                <div style="font-size:20px; font-weight:700; color:{{ $studentFee->balancePesewas() > 0 ? '#B23B3B' : '#2E7D32' }};">GHS {{ number_format($studentFee->balancePesewas() / 100, 2) }}</div>
            </div>
            <div>
                <div style="font-size:12px; color:var(--muted);">Due date</div>
                <div style="font-size:14px; margin-top:6px;">{{ $studentFee->due_date?->format('d M Y') ?? '—' }}</div>
            </div>
        </div>
    </div>

    @if ($studentFee->balancePesewas() > 0 && $studentFee->status !== 'waived')
        <div class="card card-padded" style="margin-bottom:24px;">
            <h3 style="margin-top:0;">Record a payment</h3>
            <form method="POST" action="{{ route('admin.student-fees.payments.store', $studentFee) }}" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                @csrf
                <div class="form-group" style="margin:0; width:140px;">
                    <label>Amount (GHS)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" max="{{ $studentFee->balancePesewas() / 100 }}" required>
                </div>
                <div class="form-group" style="margin:0; width:150px;">
                    <label>Method</label>
                    <select name="method" required>
                        <option value="cash">Cash</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="bank">Bank</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0; width:180px;">
                    <label>Reference (optional)</label>
                    <input type="text" name="reference" placeholder="MoMo/bank ref">
                </div>
                <div class="form-group" style="margin:0; width:170px;">
                    <label>Date paid</label>
                    <input type="date" name="paid_at" value="{{ now()->format('Y-m-d') }}">
                </div>
                <button type="submit" class="btn-submit" style="width:auto; padding:10px 24px;">Record Payment</button>
            </form>
        </div>
    @endif

    <div class="card card-padded">
        <h3 style="margin-top:0;">Payment history</h3>
        <table>
            <thead>
                <tr><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th><th>Recorded by</th></tr>
            </thead>
            <tbody>
                @forelse ($studentFee->payments as $payment)
                    <tr>
                        <td>{{ $payment->paid_at->format('d M Y') }}</td>
                        <td>GHS {{ number_format($payment->amount_pesewas / 100, 2) }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td>
                        <td>{{ $payment->reference ?? '—' }}</td>
                        <td>{{ $payment->recordedBy->full_name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center; color:var(--muted);">No payments recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
