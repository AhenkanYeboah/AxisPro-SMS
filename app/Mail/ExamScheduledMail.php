<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExamScheduledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Student $student)
    {
    }

    public function build(): self
    {
        $schoolName = $this->student->school->name;

        return $this->subject("Your {$schoolName} entrance exam has been scheduled")
            ->view('emails.exam-scheduled')
            ->with([
                'studentName' => $this->student->fullName(),
                'examDate' => $this->student->exam_date,
                'loginUrl' => route('student.login'),
                'schoolName' => $schoolName,
            ]);
    }
}
