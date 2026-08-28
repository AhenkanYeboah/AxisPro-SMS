<?php

namespace App\Support;

use App\Models\Student;
use Illuminate\Support\Facades\Schema;
use Throwable;

class StudentStats
{
    public static function compute(): array
    {
        try { $total = Student::count(); } catch (Throwable $e) { $total = 0; }
        try { $admitted = Student::where('admission_status', 'admitted')->count(); } catch (Throwable $e) { $admitted = 0; }
        try { $male = Student::where('gender', 'Male')->count(); } catch (Throwable $e) { $male = 0; }
        try { $female = Student::where('gender', 'Female')->count(); } catch (Throwable $e) { $female = 0; }

        try {
            $by_class = Student::selectRaw('class, count(*) as c')
                ->groupBy('class')
                ->orderBy('class')
                ->pluck('c', 'class');
        } catch (Throwable $e) {
            $by_class = collect();
        }

        try {
            if (Schema::hasColumn('students', 'region')) {
                $by_region = Student::selectRaw('region, count(*) as c')
                    ->whereNotNull('region')
                    ->where('region', '!=', '')
                    ->groupBy('region')
                    ->orderByDesc('c')
                    ->limit(10)
                    ->pluck('c', 'region');
            } else {
                $by_region = collect();
            }
        } catch (Throwable $e) {
            $by_region = collect();
        }

        return [
            'total' => $total,
            'admitted' => $admitted,
            'pending' => max(0, $total - $admitted),
            'male' => $male,
            'female' => $female,
            'by_class' => $by_class,
            'by_region' => $by_region,
        ];
    }

    public static function computeForClass(?string $class): array
    {
        try {
            $roster = Student::where('status', 'active')->where('class', $class);
            return [
                'class_size' => (clone $roster)->count(),
                'male' => (clone $roster)->where('gender', 'Male')->count(),
                'female' => (clone $roster)->where('gender', 'Female')->count(),
            ];
        } catch (Throwable $e) {
            return ['class_size'=>0,'male'=>0,'female'=>0];
        }
    }
}
