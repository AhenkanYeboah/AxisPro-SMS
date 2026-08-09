@extends('layouts.dashboard')

@section('title', 'Attendance')
@section('sidebar-sub', 'Teacher Dashboard')
@section('page-label', 'Attendance')
@section('welcome-message', 'Class: ' . (auth('teacher')->user()->assigned_class ?: 'Not assigned'))

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('teacher.dashboard') }}"><i class="nav-icon">▤</i> Teacher Dashboard</a>
    <a href="{{ route('teacher.attendance') }}" class="active"><i class="nav-icon">📅</i> Attendance</a>
    <a href="{{ route('teacher.assignments') }}"><i class="nav-icon">📝</i> Assignments</a>
    <a href="{{ route('teacher.timetable') }}"><i class="nav-icon">🗓</i> Timetable</a>
    <a href="{{ route('teacher.report-cards') }}"><i class="nav-icon">📊</i> Report Cards</a>
    <a href="{{ route('teacher.research-assistant') }}"><i class="nav-icon">🔎</i> Research Assistant</a>
    <a href="{{ route('teacher.virtual-classes') }}"><i class="nav-icon">🎥</i> Virtual Classes</a>
@endsection

@section('topbar-right')
    <span class="user-greeting">👤 <strong>{{ auth('teacher')->user()->teacher_id }}</strong></span>
    <form method="POST" action="{{ route('teacher.logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="auth-btn auth-btn-logout">🚪 Logout</button>
    </form>
@endsection

@section('content')
    @if ($message)
        <div class="message {{ $messageType }}">{{ $messageType === 'success' ? '✅' : '❌' }} {{ $message }}</div>
    @endif

    @if (session('status'))
        <div class="message success">✅ {{ session('status') }}</div>
    @endif

    <div class="card card-padded" style="margin-bottom:20px;">
        <div class="term-tabs">
            @for ($t = 1; $t <= 3; $t++)
                <a href="{{ route('teacher.attendance', ['term' => $t]) }}"
                   class="term-tab {{ $currentTerm == $t ? 'active' : '' }}">
                    {{ ['📘', '📗', '📕'][$t - 1] }} Term {{ $t }}
                </a>
            @endfor
        </div>
    </div>

    @if ($showSummary)
        {{-- SUMMARY VIEW: totals per term, across all 3 terms --}}
        <div class="card" style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th rowspan="2">Student</th>
                        <th>Term 1</th><th>Term 2</th><th>Term 3</th><th>Overall</th>
                    </tr>
                    <tr style="font-size:11px;">
                        <th>P / A</th><th>P / A</th><th>P / A</th><th>P / A</th>
                    </tr>
                </thead>
                <tbody>
                    @php $overallPresent = 0; $overallAbsent = 0; @endphp
                    @foreach ($students as $student)
                        @php
                            $t1p = $summaryData[$student->id][1]['present'] ?? 0;
                            $t1a = $summaryData[$student->id][1]['absent'] ?? 0;
                            $t2p = $summaryData[$student->id][2]['present'] ?? 0;
                            $t2a = $summaryData[$student->id][2]['absent'] ?? 0;
                            $t3p = $summaryData[$student->id][3]['present'] ?? 0;
                            $t3a = $summaryData[$student->id][3]['absent'] ?? 0;
                            $totalP = $t1p + $t2p + $t3p;
                            $totalA = $t1a + $t2a + $t3a;
                            $overallPresent += $totalP;
                            $overallAbsent += $totalA;
                        @endphp
                        <tr>
                            <td class="student-name">{{ $student->fullName() }}</td>
                            <td>{{ $t1p }} / {{ $t1a }}</td>
                            <td>{{ $t2p }} / {{ $t2a }}</td>
                            <td>{{ $t3p }} / {{ $t3a }}</td>
                            <td><strong>{{ $totalP }} / {{ $totalA }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align:right; font-weight:600;">Class Total (P / A)</td>
                        <td><strong>{{ $overallPresent }} / {{ $overallAbsent }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p style="margin-top:16px;">
            <a href="{{ route('teacher.attendance', ['term' => $currentTerm]) }}" class="btn-back">← Back to Attendance Entry</a>
        </p>
    @else
        {{-- ENTRY GRID: one column per weekday in the term (~80 columns).
             This is a genuinely large table by design (16 weeks x 5 days) -
             same as the original file. On smaller screens this will need
             horizontal scrolling, which the wrapping div below provides. --}}
        <div class="card" style="overflow-x:auto;">
            <form method="POST" action="{{ route('teacher.attendance.save') }}">
                @csrf
                <input type="hidden" name="term" value="{{ $currentTerm }}">
                <input type="hidden" name="save_attendance" value="1">

                <table style="min-width:{{ 260 + count($dates) * 42 }}px;">
                    <thead>
                        <tr>
                            <th rowspan="2" style="min-width:180px;">Student</th>
                            <th rowspan="2" style="min-width:70px;">ID</th>
                            @foreach ($dates as $date)
                                <th class="date-col" title="{{ $date->format('l, F j, Y') }}">
                                    {{ $date->format('M d') }}<br>
                                    <span style="font-weight:300; font-size:9px;">{{ $date->format('D') }}</span>
                                </th>
                            @endforeach
                            <th rowspan="2">✔</th>
                            <th rowspan="2">✘</th>
                        </tr>
                        <tr></tr>
                    </thead>
                    <tbody>
                        @foreach ([['label' => '👨 Male', 'group' => $male], ['label' => '👩 Female', 'group' => $female]] as $section)
                            @if ($section['group']->isNotEmpty())
                                <tr>
                                    <td colspan="{{ count($dates) + 4 }}" class="gender-header">
                                        {{ $section['label'] }} ({{ $section['group']->count() }})
                                    </td>
                                </tr>
                                @foreach ($section['group'] as $student)
                                    @php $presentCount = 0; $absentCount = 0; @endphp
                                    <tr>
                                        <td class="student-info">
                                            <span class="student-name">{{ $student->fullName() }}</span>
                                        </td>
                                        <td style="font-size:11px;">{{ $student->student_id }}</td>
                                        @foreach ($dates as $date)
                                            @php
                                                $dateStr = $date->format('Y-m-d');
                                                $status = $existingAttendance[$student->id][$dateStr] ?? '';
                                                if ($status === 'present') $presentCount++;
                                                if ($status === 'absent') $absentCount++;
                                            @endphp
                                            <td>
                                                <select name="attendance[{{ $student->id }}][{{ $dateStr }}]">
                                                    <option value="">—</option>
                                                    <option value="present" {{ $status === 'present' ? 'selected' : '' }}>✔</option>
                                                    <option value="absent" {{ $status === 'absent' ? 'selected' : '' }}>✘</option>
                                                    <option value="holiday" {{ $status === 'holiday' ? 'selected' : '' }}>H</option>
                                                </select>
                                            </td>
                                        @endforeach
                                        <td class="totals">{{ $presentCount }}</td>
                                        <td class="totals">{{ $absentCount }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                </table>

                <div class="attendance-actions" style="padding:16px 24px;">
                    <button type="submit" class="btn-primary" style="padding:10px 28px;">💾 Save Attendance (Term {{ $currentTerm }})</button>
                    <a href="{{ route('teacher.attendance', ['term' => $currentTerm, 'summary' => 1]) }}" class="btn-gold" style="padding:10px 28px;">📊 Generate All Terms Summary</a>
                    <span style="font-size:12px; color:var(--muted);">✔ = Present &nbsp;|&nbsp; ✘ = Absent &nbsp;|&nbsp; H = Holiday</span>
                </div>
            </form>
        </div>
    @endif
@endsection
