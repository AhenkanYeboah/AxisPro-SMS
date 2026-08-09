<?php

namespace App\Http\Controllers;

use App\Models\SchoolActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(): View
    {
        $activitiesByCategory = SchoolActivity::orderBy('activity_date')
            ->get()
            ->groupBy('category');

        return view('activities.index', compact('activitiesByCategory'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'activity_date' => 'required|date',
            'category' => 'nullable|string|max:50',
        ]);
        $data['category'] = $data['category'] ?? 'General';

        SchoolActivity::create($data);

        return redirect()->route('admin.dashboard')->with('status', 'Activity added.');
    }

    public function update(Request $request, SchoolActivity $activity): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'activity_date' => 'required|date',
            'category' => 'nullable|string|max:50',
        ]);
        $data['category'] = $data['category'] ?? 'General';

        $activity->update($data);

        return redirect()->route('admin.dashboard')->with('status', 'Activity updated.');
    }

    public function destroy(SchoolActivity $activity): RedirectResponse
    {
        $activity->delete();

        return redirect()->route('admin.dashboard')->with('status', 'Activity deleted.');
    }
}
