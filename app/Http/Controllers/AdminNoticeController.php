<?php

namespace App\Http\Controllers;

use App\Jobs\SendNoticeJob;
use App\Models\Notice;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminNoticeController extends Controller
{
    public function index(): View
    {
        $notices = Notice::withCount('recipients')
            ->orderByDesc('created_at')
            ->get();

        $students = Student::where('status', 'active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'class']);

        return view('admin.notices.index', compact('notices', 'students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'body' => 'required|string|max:2000',
            'audience' => 'required|in:all,class,individual',
            'class' => 'nullable|string|max:50|required_if:audience,class',
            'student_id' => 'nullable|exists:students,id|required_if:audience,individual',
            'channel' => 'required|in:email,sms,both',
        ]);

        $notice = Notice::create([
            'sent_by_admin_id' => Auth::guard('admin')->id(),
            'title' => $data['title'],
            'body' => $data['body'],
            'audience' => $data['audience'],
            'class' => $data['audience'] === 'class' ? $data['class'] : null,
            'channel' => $data['channel'],
            'status' => 'draft',
        ]);

        SendNoticeJob::dispatch($notice, $data['audience'] === 'individual' ? (int) $data['student_id'] : null);

        return redirect()->route('admin.notices.index')->with('status', 'Notice queued for sending. Delivery status will update shortly.');
    }

    public function show(Notice $notice): View
    {
        $notice->load(['recipients.student', 'sentBy']);

        return view('admin.notices.show', compact('notice'));
    }
}
