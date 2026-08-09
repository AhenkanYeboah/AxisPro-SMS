@extends('layouts.public')

@section('title', 'AxisPro School Management System')

@section('content')
<div class="landing-page">
    <div class="landing-left">
        <div class="landing-left-content">
            <img src="{{ asset('images/axispro-logo.webp') }}" alt="AxisPro" style="width:120px; height:120px; object-fit:contain; border-radius:20px;">
            <h1 style="font-size:32px;">AxisPro<br><em>School</em><br>Management</h1>
            <p class="motto">One platform. Every school.</p>
        </div>
    </div>
    <div class="landing-right">
        <p class="eyebrow">For School Owners &amp; Administrators</p>
        <h2>Run your whole school from one place.</h2>
        <p class="tagline">
            Admissions, attendance, exams, assignments, report cards, and teacher/student
            portals - AxisPro gives every school its own private, fully branded system,
            without needing to build or host anything yourself.
        </p>

        <ul class="feature-list">
            <li><span class="feat-dot"></span>Your own admissions portal &amp; applicant tracking</li>
            <li><span class="feat-dot"></span>Entrance exams, assignments &amp; report cards online</li>
            <li><span class="feat-dot"></span>Dedicated admin, teacher &amp; student dashboards</li>
            <li><span class="feat-dot"></span>Your school's own name, logo, and colors</li>
        </ul>

        <a href="{{ route('school.signup') }}" class="portal-link">Start Your School <span class="arrow">→</span></a>

        <div style="margin-top:24px; padding-top:20px; border-top:1px solid rgba(255,255,255,0.15);">
            @if (app()->environment('production'))
                {{-- Only offered in production, where SAAS_BASE_DOMAIN is a
                     real domain with wildcard subdomain DNS pointed at it -
                     see config/saas.php. Locally this can never resolve
                     (no real DNS, and a browser can't do subdomains of an
                     IP/localhost anyway), so showing it there is just a
                     dead end - the dev branch below replaces it with a
                     link that actually works on a local setup. --}}
                <p style="font-size:12px; margin-bottom:8px; opacity:0.85;">Already have a school on AxisPro? Enter your school's subdomain to go to its login page:</p>
                <form id="school-login-form" style="display:flex; gap:8px;" onsubmit="event.preventDefault(); goToSchoolLogin();">
                    <input type="text" id="school-subdomain" placeholder="yourschool" style="flex:1; padding:8px 12px; border-radius:6px; border:1px solid rgba(255,255,255,0.3); background:rgba(255,255,255,0.1); color:inherit;">
                    <button type="submit" class="portal-link" style="padding:8px 16px; font-size:13px;">Log In</button>
                </form>
                <p style="margin-top:4px; font-size:11px; opacity:0.7;">e.g. <code>yourschool.{{ config('saas.base_domain') }}</code></p>
            @else
                {{-- Local dev: ResolveTenant falls back to the first seeded
                     school for any non-root path outside production (see
                     ResolveTenant::resolveSchool), so a direct link to
                     /admin/login works out of the box without any DNS/
                     hosts-file setup. Styled as a real button (not a bare
                     underlined link) to match Start Your School below it. --}}
                <p style="font-size:12px; margin-bottom:10px; opacity:0.85;">Already have a school on AxisPro?</p>
                <a href="{{ route('admin.login') }}" class="portal-link" style="display:inline-block; padding:8px 16px; font-size:13px;">School Admin Login <span class="arrow">→</span></a>
            @endif
        </div>

        <a href="{{ route('platform.login') }}" class="portal-link" style="display:inline-block; margin-top:12px; padding:8px 16px; font-size:13px; opacity:0.85;">Platform Administrator Login <span class="arrow">→</span></a>

        <p class="landing-footer-note">A product by <a href="https://www.facebook.com/share/19E5UdnunZ/" target="_blank" rel="noopener noreferrer" style="color:inherit; text-decoration:underline;">AxisPro Consult</a></p>
    </div>
</div>

@if (app()->environment('production'))
<script>
    function goToSchoolLogin() {
        const subdomain = document.getElementById('school-subdomain').value.trim().toLowerCase();
        if (!subdomain) return;
        // Preserves the current protocol so this works whether AxisPro is
        // served over http or https - doesn't assume production's scheme.
        window.location.href = window.location.protocol + '//' + subdomain + '.{{ config('saas.base_domain') }}/admin/login';
    }
</script>
@endif
@endsection
