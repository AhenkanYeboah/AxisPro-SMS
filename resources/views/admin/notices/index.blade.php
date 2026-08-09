@extends('layouts.dashboard')

@section('title', 'Notices')
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Notices')
@section('welcome-message', 'Send email/SMS notices to parents')

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.form') }}"><i class="nav-icon">✎</i> Enrollment Form</a>
    <a href="{{ route('admin.dashboard') }}"><i class="nav-icon">▤</i> Dashboard</a>
    <a href="{{ route('admin.exams.index') }}"><i class="nav-icon">📝</i> Entrance Exams</a>
    <a href="{{ route('admin.invites.index') }}"><i class="nav-icon">✉</i> Invites</a>
    <a href="{{ route('admin.fees.index') }}"><i class="nav-icon">💳</i> Billing</a>
    <a href="{{ route('admin.notices.index') }}" class="active"><i class="nav-icon">📣</i> Notices</a>
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

    <div class="card card-padded" style="margin-bottom:24px;">
        <h3 style="margin-top:0;">Compose a notice</h3>
        <form method="POST" action="{{ route('admin.notices.store') }}" id="notice-form">
            @csrf
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required maxlength="200">
            </div>
            <div class="form-group">
                <label>Message</label>
                <textarea name="body" rows="4" required maxlength="2000"></textarea>
            </div>
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <div class="form-group" style="width:180px;">
                    <label>Audience</label>
                    <select name="audience" id="notice-audience" onchange="document.getElementById('notice-class-field').style.display = this.value === 'class' ? 'block' : 'none'; document.getElementById('notice-student-field').style.display = this.value === 'individual' ? 'block' : 'none';">
                        <option value="all">All students</option>
                        <option value="class">One class</option>
                        <option value="individual">One student</option>
                    </select>
                </div>
                <div class="form-group" id="notice-class-field" style="width:180px; display:none;">
                    <label>Class</label>
                    <select name="class">
                        @foreach (['Creche', 'Nursery 1', 'Nursery 2', 'Kindergarten 1', 'Kindergarten 2', 'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6', 'JHS 1', 'JHS 2', 'JHS 3'] as $class)
                            <option value="{{ $class }}">{{ $class }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" id="notice-student-field" style="width:260px; display:none;">
                    <label>Student</label>
                    <input type="text" id="notice-student-search" list="notice-student-list" placeholder="Start typing a name…" autocomplete="off">
                    <datalist id="notice-student-list">
                        @foreach ($students as $s)
                            <option data-id="{{ $s->id }}" value="{{ $s->fullName() }} — {{ $s->class }}"></option>
                        @endforeach
                    </datalist>
                    <input type="hidden" name="student_id" id="notice-student-id">
                    <span id="notice-student-hint" style="font-size:11px; color:var(--muted);">Pick a name from the list as you type.</span>
                </div>
                <div class="form-group" style="width:160px;">
                    <label>Send via</label>
                    <select name="channel">
                        <option value="both">Email + SMS</option>
                        <option value="email">Email only</option>
                        <option value="sms">SMS only</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-submit" style="width:auto; padding:10px 24px;">Send Notice</button>
        </form>
    </div>

    <div class="card card-padded">
        <h3 style="margin-top:0;">Sent notices</h3>
        <table>
            <thead>
                <tr><th>Title</th><th>Audience</th><th>Channel</th><th>Recipients</th><th>Status</th><th>Date</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($notices as $notice)
                    <tr>
                        <td>{{ $notice->title }}</td>
                        <td>{{ $notice->audience === 'class' ? $notice->class : ucfirst($notice->audience) }}</td>
                        <td>{{ ucfirst($notice->channel) }}</td>
                        <td>{{ $notice->recipients_count }}</td>
                        <td>
                            @if ($notice->status === 'sent')
                                <span style="color:#2E7D32;">Sent</span>
                            @elseif ($notice->status === 'sending')
                                <span style="color:#B8860B;">Sending…</span>
                            @elseif ($notice->status === 'failed')
                                <span style="color:#B23B3B;">Failed (partial)</span>
                            @else
                                <span style="color:var(--muted);">Draft</span>
                            @endif
                        </td>
                        <td style="font-size:12px; color:var(--muted);">{{ $notice->created_at->format('d M Y, g:ia') }}</td>
                        <td><a href="{{ route('admin.notices.show', $notice) }}" style="font-size:12px;">Delivery status →</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center; color:var(--muted);">No notices sent yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var searchInput = document.getElementById('notice-student-search');
            var hiddenId = document.getElementById('notice-student-id');
            var hint = document.getElementById('notice-student-hint');
            var options = document.querySelectorAll('#notice-student-list option');
            var form = document.getElementById('notice-form');
            var audienceSelect = document.getElementById('notice-audience');

            // Native <datalist> only gives us the typed text on 'input', not
            // which option was picked - so we match the typed value against
            // the option list ourselves and pull its data-id.
            function resolveStudent() {
                var typed = searchInput.value.trim();
                var match = null;

                options.forEach(function (opt) {
                    if (opt.value === typed) {
                        match = opt;
                    }
                });

                if (match) {
                    hiddenId.value = match.getAttribute('data-id');
                    hint.textContent = 'Selected ✓';
                    hint.style.color = '#2E7D32';
                } else {
                    hiddenId.value = '';
                    hint.textContent = 'Pick a name from the list as you type.';
                    hint.style.color = '';
                }
            }

            if (searchInput) {
                searchInput.addEventListener('input', resolveStudent);
            }

            form.addEventListener('submit', function (e) {
                if (audienceSelect.value === 'individual' && !hiddenId.value) {
                    e.preventDefault();
                    hint.textContent = 'Please pick a student from the suggestions before sending.';
                    hint.style.color = '#B23B3B';
                    searchInput.focus();
                    return;
                }

                if (!confirm('Send this notice now?')) {
                    e.preventDefault();
                }
            });
        })();
    </script>
@endpush
