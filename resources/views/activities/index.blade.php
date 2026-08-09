@extends('layouts.public')

@section('title', 'School Activities')

@section('content')
<div class="activities-page">
    <h1>📋 School Activities & Events</h1>
    <p class="sub">Explore all the vibrant activities at {{ $currentSchool->name ?? 'our school' }}.</p>

    @forelse ($activitiesByCategory as $category => $categoryActivities)
        <div class="category-section">
            <h2>{{ $category }}</h2>
            <div class="activity-grid">
                @foreach ($categoryActivities as $activity)
                    <div class="activity-card">
                        <div class="title">{{ $activity->title }}</div>
                        <div class="desc">{{ $activity->description }}</div>
                        <div class="date">📅 {{ $activity->activity_date->format('M d, Y') }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <p style="color:var(--muted);">No activities have been added yet. Check back soon!</p>
    @endforelse
</div>
@endsection
