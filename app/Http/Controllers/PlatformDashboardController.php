<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Platform-owner view across ALL schools. Every query here is deliberately
 * unscoped (School/Payment models query the base table directly - School
 * doesn't use BelongsToSchool at all, and this controller runs on the
 * 'platform' guard, never 'admin') so you can see the whole business.
 */
class PlatformDashboardController extends Controller
{
    public function index(): View
    {
        $schools = School::withCount(['admins', 'teachers', 'students'])
            ->latest('id')
            ->get();

        return view('platform.dashboard', [
            'schools' => $schools,
            'stats' => [
                'total_schools' => $schools->count(),
                'trial_schools' => $schools->where('status', 'trial')->count(),
                'active_schools' => $schools->where('status', 'active')->count(),
                'suspended_schools' => $schools->where('status', 'suspended')->count(),
            ],
        ]);
    }

    public function show(School $school): View
    {
        $school->load(['payments' => fn ($q) => $q->latest()->limit(20)]);

        return view('platform.school-show', ['school' => $school]);
    }

    public function suspend(School $school): RedirectResponse
    {
        $school->update(['status' => 'suspended']);

        return back()->with('success', "{$school->name} has been suspended.");
    }

    public function reactivate(School $school): RedirectResponse
    {
        // Reactivating doesn't guess at payment status - it just lifts the
        // suspension. If their subscription/trial has genuinely lapsed,
        // isActive() will still correctly block them until they pay or you
        // extend their dates explicitly below.
        $school->update(['status' => $school->subscription_ends_at ? 'active' : 'trial']);

        return back()->with('success', "{$school->name} has been reactivated.");
    }

    public function extendTrial(Request $request, School $school): RedirectResponse
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $base = $school->trial_ends_at && $school->trial_ends_at->isFuture()
            ? $school->trial_ends_at
            : now();

        $school->update(['trial_ends_at' => $base->addDays($validated['days'])]);

        return back()->with('success', "Trial extended by {$validated['days']} days.");
    }
}
