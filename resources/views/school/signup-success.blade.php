@extends('layouts.public')

@section('title', 'Your School Is Ready — AxisPro School Management System')

@section('content')
<div class="auth-page">
    <div class="auth-card" style="max-width:460px; text-align:center;">
        <h2 style="margin:0;">🎉 {{ $school->name }} is ready!</h2>
        <p class="sub">Your school now has its own private instance. Bookmark this link — it's yours alone.</p>

        <div style="background:#F1EBFB; border-radius:8px; padding:16px; margin:20px 0; word-break:break-all;">
            <a href="{{ $loginUrl }}" style="font-weight:600; color:#5B3A9E;">{{ $loginUrl }}</a>
        </div>

        <p style="font-size:13px; color:var(--muted);">
            Log in there with the admin username and password you just created.
            You're currently on a {{ config('saas.trial_days') }}-day trial —
            no payment needed to explore the full system.
        </p>

        <a href="{{ $loginUrl }}" class="btn-submit" style="display:inline-block; text-decoration:none; margin-top:12px;">Go to My School →</a>
    </div>
</div>
@endsection
