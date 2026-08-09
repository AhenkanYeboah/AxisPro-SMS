<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\VirtualClass;
use App\Services\ZoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Teacher-facing virtual class scheduling. Scoped to the teacher's own
 * assigned class_level, same pattern as ResearchAssistantController -
 * one teacher, one class, any of the classe's subjects.
 */
class VirtualClassController extends Controller
{
    public function index(ZoomService $zoom): View
    {
        $teacher = Auth::guard('teacher')->user();
        $classLevel = $teacher->classLevel;

        $subjects = $classLevel?->curriculum_id
            ? Subject::where('curriculum_id', $classLevel->curriculum_id)->orderBy('name')->get()
            : collect();

        $classes = $classLevel
            ? VirtualClass::where('class_level_id', $classLevel->id)
                ->where('teacher_id', $teacher->id)
                ->orderByDesc('scheduled_start')
                ->get()
            : collect();

        return view('teacher.virtual-classes', [
            'teacher' => $teacher,
            'classLevel' => $classLevel,
            'subjects' => $subjects,
            'classes' => $classes,
            'zoomAvailable' => $zoom->isConfigured(),
            'noClassAssigned' => ! $classLevel,
        ]);
    }

    public function store(Request $request, ZoomService $zoom): RedirectResponse
    {
        $teacher = Auth::guard('teacher')->user();
        $classLevel = $teacher->classLevel;

        if (! $classLevel) {
            return back()->with('error', 'You need a class assigned before scheduling a virtual class - contact your school admin.');
        }

        $data = $request->validate([
            'subject_id' => 'nullable|exists:subjects,id',
            'title' => 'required|string|max:200',
            'platform' => 'required|in:zoom_api,jitsi_auto,external_link',
            'external_url' => 'required_if:platform,external_link|nullable|url|max:500',
            'scheduled_start' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:10|max:180',
        ]);

        $start = \Carbon\Carbon::parse($data['scheduled_start']);
        $end = $start->clone()->addMinutes((int) $data['duration_minutes']);

        $zoomMeetingId = null;

        switch ($data['platform']) {
            case 'zoom_api':
                if (! $zoom->isConfigured()) {
                    return back()->with('error', 'Zoom isn\'t configured for this platform yet. Ask your platform admin, or use Jitsi (Quick Meeting) instead.')->withInput();
                }

                try {
                    $meeting = $zoom->createMeeting($data['title'], $start, (int) $data['duration_minutes']);
                    $joinUrl = $meeting['join_url'];
                    $zoomMeetingId = $meeting['meeting_id'];
                } catch (\Throwable $e) {
                    return back()->with('error', 'Could not create the Zoom meeting: '.$e->getMessage())->withInput();
                }

                break;

            case 'jitsi_auto':
                // Zero setup, always works - a random unguessable room
                // name under Jitsi's free public server. Good enough for
                // a class of this size; a school that outgrows the free
                // Jitsi server can self-host later without any schema
                // change (still just a join_url).
                $joinUrl = 'https://meet.jit.si/AxisPro-'.Str::random(10);
                break;

            case 'external_link':
                $joinUrl = $data['external_url'];
                break;

            default:
                return back()->with('error', 'Invalid platform selected.')->withInput();
        }

        VirtualClass::create([
            'school_id' => $teacher->school_id,
            'teacher_id' => $teacher->id,
            'class_level_id' => $classLevel->id,
            'subject_id' => $data['subject_id'] ?? null,
            'title' => $data['title'],
            'platform' => $data['platform'],
            'join_url' => $joinUrl,
            'zoom_meeting_id' => $zoomMeetingId,
            'scheduled_start' => $start,
            'scheduled_end' => $end,
            'status' => 'scheduled',
        ]);

        return redirect()->route('teacher.virtual-classes')->with('success', 'Virtual class scheduled.');
    }

    public function cancel(VirtualClass $virtualClass): RedirectResponse
    {
        $teacher = Auth::guard('teacher')->user();

        abort_unless($virtualClass->teacher_id === $teacher->id, 403);

        // Local cancellation only - does NOT call Zoom's API to delete the
        // meeting server-side even for platform='zoom_api'. Deliberately
        // simple for this phase: a stale, unused Zoom meeting expiring on
        // its own is harmless, whereas adding a second external API call
        // on the cancel path (with its own failure handling) isn't worth
        // it yet. Worth revisiting if Zoom account meeting-count limits
        // ever become a real constraint.
        $virtualClass->update(['status' => 'cancelled']);

        return back()->with('success', 'Virtual class cancelled.');
    }
}
