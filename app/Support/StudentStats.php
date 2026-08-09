<?php

namespace App\Support;

use App\Models\Student;

class StudentStats
{
    // Replaces the 6 separate COUNT(*) queries in the original file.
    // Each Eloquent call here still hits the database once - this is purely
    // about organizing the queries in one reusable place instead of copy-pasted
    // in every page that needs them.
    public static function compute(): array
    {
        $total = Student::count();
        $admitted = Student::where('admission_status', 'admitted')->count();

        return [
            'total' => $total,
            'admitted' => $admitted,
            'pending' => $total - $admitted, // matches original: derived, not a status='pending' count
            'male' => Student::where('gender', 'Male')->count(),
            'female' => Student::where('gender', 'Female')->count(),
            'by_class' => Student::selectRaw('class, count(*) as c')
                ->groupBy('class')
                ->orderBy('class')
                ->pluck('c', 'class'),
            'by_region' => Student::selectRaw('region, count(*) as c')
                ->whereNotNull('region')
                ->where('region', '!=', '')
                ->groupBy('region')
                ->orderByDesc('c')
                ->limit(10)
                ->pluck('c', 'region'),
        ];
    }

    // Scoped to one class, for the teacher dashboard. A teacher should only
    // ever see numbers about their own class, not school-wide admission
    // stats (that's admin-only data and was previously leaking into the
    // teacher view via compute()).
    public static function computeForClass(?string $class): array
    {
        $roster = Student::where('status', 'active')->where('class', $class);

        return [
            'class_size' => (clone $roster)->count(),
            'male' => (clone $roster)->where('gender', 'Male')->count(),
            'female' => (clone $roster)->where('gender', 'Female')->count(),
        ];
    }
}
