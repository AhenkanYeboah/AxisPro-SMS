<?php

namespace App\Jobs;

use App\Mail\NoticeMail;
use App\Models\Notice;
use App\Models\NoticeRecipient;
use App\Models\Student;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Sends one notice to every resolved recipient. Runs as a queued job rather
 * than inline in the controller - "all parents in Class 3" is 30-40 HTTP
 * calls to Arkesel/mail, which would otherwise block the admin's request
 * or time out. Writes to notice_recipients as it goes so an admin can see
 * partial progress/failures on the notice's detail page rather than a
 * spinning page or an opaque success/fail.
 */
class SendNoticeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Notice $notice, public ?int $singleStudentId = null)
    {
    }

    public function handle(SmsService $sms): void
    {
        $this->notice->update(['status' => 'sending']);

        $students = $this->resolveRecipients();

        if ($students->isEmpty()) {
            $this->notice->update(['status' => 'failed']);

            return;
        }

        $anyFailure = false;

        foreach ($students as $student) {
            $recipient = NoticeRecipient::firstOrCreate([
                'notice_id' => $this->notice->id,
                'student_id' => $student->id,
            ]);

            if (in_array($this->notice->channel, ['email', 'both'], true)) {
                $this->sendEmail($student, $recipient);
            } else {
                $recipient->update(['email_status' => 'skipped']);
            }

            if (in_array($this->notice->channel, ['sms', 'both'], true)) {
                $this->sendSms($student, $recipient, $sms);
            } else {
                $recipient->update(['sms_status' => 'skipped']);
            }

            if ($recipient->fresh()->email_status === 'failed' || $recipient->fresh()->sms_status === 'failed') {
                $anyFailure = true;
            }
        }

        $this->notice->update(['status' => $anyFailure ? 'failed' : 'sent']);
    }

    private function resolveRecipients()
    {
        if ($this->singleStudentId) {
            return Student::where('id', $this->singleStudentId)->get();
        }

        return $this->notice->targetStudents();
    }

    private function sendEmail(Student $student, NoticeRecipient $recipient): void
    {
        $email = $student->contactEmail();

        if (! $email) {
            $recipient->update(['email_status' => 'skipped']);

            return;
        }

        try {
            Mail::to($email)->send(new NoticeMail($this->notice, $student));
            $recipient->update(['email_status' => 'sent']);
        } catch (\Throwable $e) {
            $recipient->update([
                'email_status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 255),
            ]);
        }
    }

    private function sendSms(Student $student, NoticeRecipient $recipient, SmsService $sms): void
    {
        $phone = $student->contactPhone();

        if (! $phone) {
            $recipient->update(['sms_status' => 'skipped']);

            return;
        }

        $message = "{$this->notice->school->name}: {$this->notice->title}\n{$this->notice->body}";
        $ok = $sms->send($phone, $message);

        $recipient->update([
            'sms_status' => $ok ? 'sent' : 'failed',
            'error_message' => $ok ? null : ($recipient->error_message ?: 'SMS delivery failed - check Arkesel configuration and phone number.'),
        ]);
    }
}
