@extends('layouts.dashboard')

@section('title', 'Billing — Fees & Bills')
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Billing')
@section('welcome-message', 'Fees, Bills & Payments')

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

    {{-- SUMMARY --}}
    <div class="stats-grid" style="margin-bottom:24px;">
        <div class="stat-card"><div class="stat-icon total"><div class="stat-info"><div class="stat-number">GHS {{ number_format($summary['total_billed'] / 100, 2) }}</div><div class="stat-label">Total Billed</div></div></div></div>
        <div class="stat-card"><div class="stat-icon admitted"><div class="stat-info"><div class="stat-number">GHS {{ number_format($summary['total_collected'] / 100, 2) }}</div><div class="stat-label">Total Collected</div></div></div></div>
        <div class="stat-card"><div class="stat-icon pending"><div class="stat-info"><div class="stat-number">{{ $summary['partially_paid_count'] }}</div><div class="stat-label">Partially Paid</div></div></div></div>
        <div class="stat-card"><div class="stat-icon female"><div class="stat-info"><div class="stat-number">{{ $summary['unpaid_count'] }}</div><div class="stat-label">Unpaid</div></div></div></div>
    </div>

    {{-- CREATE FEE ITEM --}}
    <div class="card card-padded" style="margin-bottom:24px;">
        <h3 style="margin-top:0;">Create a fee item</h3>
        <p style="font-size:13px; color:var(--muted);">
            Define a chargeable item (e.g. Term 1 fees, lunch, a trip levy), then use "Assign" below to bill it to students.
        </p>
        <form method="POST" action="{{ route('admin.fees.store') }}" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
            @csrf
            <div class="form-group" style="margin:0; flex:1; min-width:180px;">
                <label>Name</label>
                <input type="text" name="name" placeholder="e.g., Term 1 Fees" required>
            </div>
            <div class="form-group" style="margin:0; width:140px;">
                <label>Amount (GHS)</label>
                <input type="number" name="amount" step="0.01" min="0.01" required>
            </div>
            <div class="form-group" style="margin:0; width:160px;">
                <label>Class (blank = all)</label>
                <select name="class">
                    <option value="">All classes</option>
                    @foreach (['Creche', 'Nursery 1', 'Nursery 2', 'Kindergarten 1', 'Kindergarten 2', 'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6', 'JHS 1', 'JHS 2', 'JHS 3'] as $class)
                        <option value="{{ $class }}">{{ $class }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0; width:140px;">
                <label>Frequency</label>
                <select name="frequency" required>
                    <option value="termly">Termly</option>
                    <option value="monthly">Monthly</option>
                    <option value="one_off">One-off</option>
                </select>
            </div>
            <div class="form-group" style="margin:0; width:120px;">
                <label>Term (optional)</label>
                <input type="text" name="term" placeholder="Term 1">
            </div>
            <div class="form-group" style="margin:0; width:140px;">
                <label>Academic year</label>
                <input type="text" name="academic_year" placeholder="2026/2027">
            </div>
            <button type="submit" class="btn-submit" style="width:auto; padding:10px 24px;">Create</button>
        </form>
    </div>

    {{-- FEE ITEMS LIST + ASSIGN --}}
    <div class="card card-padded" style="margin-bottom:24px;">
        <h3 style="margin-top:0;">Fee items</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th><th>Amount</th><th>Class</th><th>Frequency</th><th>Assigned to</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($feeItems as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>GHS {{ number_format($item->amount_pesewas / 100, 2) }}</td>
                        <td>{{ $item->class ?? 'All classes' }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $item->frequency)) }}</td>
                        <td>{{ $item->student_fees_count }} student(s)</td>
                        <td>
                            @if ($item->is_active)
                                <span style="color:#2E7D32;">Active</span>
                            @else
                                <span style="color:var(--muted);">Inactive</span>
                            @endif
                        </td>
                        <td style="display:flex; gap:6px; flex-wrap:wrap;">
                            <button type="button" class="auth-btn" style="padding:4px 10px; font-size:11px;" onclick="document.getElementById('assign-form-{{ $item->id }}').style.display = document.getElementById('assign-form-{{ $item->id }}').style.display === 'none' ? 'flex' : 'none';">Assign</button>
                            @if ($item->is_active)
                                <form method="POST" action="{{ route('admin.fees.destroy', $item) }}" onsubmit="return confirm('Remove this fee item?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="auth-btn auth-btn-logout" style="padding:4px 10px; font-size:11px;">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    <tr id="assign-form-{{ $item->id }}" style="display:none;">
                        <td colspan="7" style="background:var(--bg-subtle, #F6F4EF);">
                            <form method="POST" action="{{ route('admin.fees.assign', $item) }}" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; padding:12px 0;">
                                @csrf
                                <div class="form-group" style="margin:0; width:180px;">
                                    <label>Class (blank = follow item's own scope)</label>
                                    <select name="class">
                                        <option value="">— use item's class —</option>
                                        @foreach (['Creche', 'Nursery 1', 'Nursery 2', 'Kindergarten 1', 'Kindergarten 2', 'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6', 'JHS 1', 'JHS 2', 'JHS 3'] as $class)
                                            <option value="{{ $class }}">{{ $class }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group" style="margin:0; width:160px;">
                                    <label>Due date (optional)</label>
                                    <input type="date" name="due_date">
                                </div>
                                <button type="submit" class="btn-submit" style="width:auto; padding:8px 20px;">Assign & Notify Parents</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center; color:var(--muted);">No fee items yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- RECENT STUDENT FEES --}}
    <div class="card card-padded">
        <h3 style="margin-top:0;">Recent student fees</h3>
        <table>
            <thead>
                <tr>
                    <th>Student</th><th>Fee</th><th>Amount</th><th>Status</th><th>Due</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($studentFees as $fee)
                    <tr>
                        <td>{{ $fee->student->fullName() }}</td>
                        <td>{{ $fee->feeItem->name }}</td>
                        <td>GHS {{ number_format($fee->amount_pesewas / 100, 2) }}</td>
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
                        <td><a href="{{ route('admin.student-fees.show', $fee) }}" style="font-size:12px;">View / Record Payment →</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center; color:var(--muted);">No fees assigned yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
