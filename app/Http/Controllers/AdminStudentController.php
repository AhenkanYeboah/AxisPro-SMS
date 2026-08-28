public function dashboard(Request $request): View|RedirectResponse
{
    // If ?debug=1 show JSON error instead of 500
    if ($request->has('debug')) {
        try {
            $admin = auth('admin')->user();
            $school = $admin->school ?? null;
            $query = \App\Models\Student::query();
            if ($school) $query->where('school_id', $school->id);
            $students = $query->limit(1)->get();
            $stats = \App\Support\StudentStats::compute();
            return response()->json([
                'admin' => $admin->only(['id','email','school_id','username']),
                'school' => $school,
                'stats' => $stats,
                'students_count' => $students->count(),
                'ok' => true
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    try {
        $school = null;
        try { if (app()->bound('currentSchool')) $school = app('currentSchool'); } catch (\Throwable $e) {}
        if (!$school && auth('admin')->check()) {
            try {
                $admin = auth('admin')->user();
                $school = $admin->school ?? ($admin->school_id ? \App\Models\School::find($admin->school_id) : null);
            } catch (\Throwable $e) {}
        }

        $query = \App\Models\Student::query();
        if ($school && isset($school->id)) $query->where('school_id', $school->id);

        $students = collect(); $recentApplicants = collect(); $classRoster = collect(); $allActivities = collect();
        try { $students = (clone $query)->orderByDesc('created_at')->get(); } catch (\Throwable $e) {}
        try { $recentApplicants = (clone $query)->orderByDesc('created_at')->limit(10)->get(); } catch (\Throwable $e) {}
        try { $classRoster = (clone $query)->where('status','active')->orderBy('class')->get()->groupBy(fn($s)=>$s->class ?: 'Not Specified'); } catch (\Throwable $e) {}

        try { $stats = \App\Support\StudentStats::compute(); }
        catch (\Throwable $e) { $stats = ['total'=>0,'admitted'=>0,'pending'=>0,'male'=>0,'female'=>0,'by_class'=>collect(),'by_region'=>collect()]; }

        try { $activitiesQuery = \App\Models\SchoolActivity::query(); if($school) $activitiesQuery->where('school_id',$school->id); $allActivities = $activitiesQuery->orderBy('activity_date')->get(); } catch (\Throwable $e) {}

        // Ensure stats is always array
        $stats = is_array($stats) ? $stats : (array)$stats;

        return view('admin.dashboard', compact('students','recentApplicants','classRoster','stats','allActivities','school'));
    } catch (\Throwable $e) {
        // Log to laravel.log so you can see in Render logs
        \Illuminate\Support\Facades\Log::error('DASHBOARD CRASH: '.$e->getMessage().' '.$e->getFile().':'.$e->getLine());
        // Fallback to super minimal view that cannot crash
        return response()->view('admin.dashboard', [
            'students'=>collect(),'recentApplicants'=>collect(),'classRoster'=>collect(),
            'stats'=>['total'=>0,'admitted'=>0,'pending'=>0,'male'=>0,'female'=>0,'by_class'=>collect(),'by_region'=>collect()],
            'allActivities'=>collect(),'school'=>null,'error'=>$e->getMessage().' at '.$e->getFile().':'.$e->getLine()
        ]);
    }
}
