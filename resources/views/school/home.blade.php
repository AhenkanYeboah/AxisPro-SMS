@extends('layouts.public')

@section('title', 'Royal Countryside Academy — Admissions Portal')

@section('content')
{{--
    The shared app.css theme (--green-deep, --gold, etc.) was repurposed to a
    neutral platform purple as part of going multi-tenant - correct for the
    generic per-school template and the AxisPro platform pages, but RCA's
    own homepage below still uses those same variable names for its deep
    green/gold look. Redeclaring them here, scoped to just this page,
    restores RCA's actual brand colors without touching the shared
    stylesheet everything else now correctly relies on.
--}}
<div class="landing-page" style="--green-deep:#0D3B2E; --green-mid:#145C41; --gold:#C9A84C; --gold-light:#FBF3DE;">
    <div class="landing-left">
        <div class="landing-left-content">
            <img src="{{ asset('crest.png') }}" alt="RCA Crest"
                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\'%3E%3Ccircle cx=\'50\' cy=\'50\' r=\'45\' fill=\'%230D3B2E\'/%3E%3Ctext x=\'50\' y=\'58\' text-anchor=\'middle\' fill=\'%23C9A84C\' font-size=\'22\' font-weight=\'bold\'%3ERCA%3C/text%3E%3C/svg%3E'">
            <h1>Royal<br><em>Countryside</em><br>Academy</h1>
            <p class="motto">Excellence · Integrity · Leadership</p>
            <p style="color:rgba(255,255,255,0.35); font-size:11px; letter-spacing:0.1em;">EAST LEGON · ACCRA · GHANA</p>
        </div>
    </div>
    <div class="landing-right">
        <p class="eyebrow">Student Admission Portal — 2025/2026</p>
        <h2>Raising royal leaders for a better Ghana.</h2>
        <p class="tagline">Welcome to the RCA Admissions Portal. Complete your enrollment application online and track your admission status through your personalised dashboard.</p>
        <ul class="feature-list">
            <li><span class="feat-dot"></span>Accredited by Ghana Education Service (GES)</li>
            <li><span class="feat-dot"></span>British Curriculum &amp; Ghana Education Service Curriculum</li>
            <li><span class="feat-dot"></span>Modern facilities and experienced faculty</li>
            <li><span class="feat-dot"></span>Real-time admission status updates</li>
        </ul>

        <div style="margin:18px 0; padding:14px 16px; border-radius:8px; background:rgba(201,168,76,0.12); border:1px solid rgba(201,168,76,0.35);">
            <p style="margin:0; font-size:13px; color:var(--green-deep); line-height:1.5;">
                🎓 <strong>Coming soon:</strong> our Senior High School will prepare students for
                international qualifications — Cambridge O &amp; A Levels — opening doors to universities worldwide.
            </p>
        </div>
        <a href="{{ route('student.form') }}" class="portal-link">Begin Enrollment <span class="arrow">→</span></a>
        <p style="margin-top:12px; font-size:13px;">
            Already enrolled? <a href="{{ route('student.login') }}" style="color:var(--green-deep); font-weight:600;">Log in here</a>
        </p>
        <p style="margin-top:6px; font-size:13px;">
            <a href="{{ route('activities.index') }}" style="color:var(--green-deep); font-weight:600;">📋 View School Activities</a>
        </p>
        <p style="margin-top:6px; font-size:13px;">
            <a href="{{ route('admin.login') }}" style="color:var(--green-deep); font-weight:600;">🔐 Admin Login</a>
            &nbsp;·&nbsp;
            <a href="{{ route('teacher.login') }}" style="color:var(--green-deep); font-weight:600;">🔐 Teacher Login</a>
        </p>
        <p class="landing-footer-note">📞 +233 (0) 30 212 3456 &nbsp;·&nbsp; admissions@royalcountryside.edu.gh</p>
    </div>
</div>
@endsection

