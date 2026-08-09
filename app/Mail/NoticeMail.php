<?php

namespace App\Mail;

use App\Models\Notice;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Notice $notice, public Student $student)
    {
    }

    public function build(): self
    {
        return $this->subject("{$this->notice->school->name}: {$this->notice->title}")
            ->view('emails.notice')
            ->with([
                'studentName' => $this->student->fullName(),
                'title' => $this->notice->title,
                'body' => $this->notice->body,
                'schoolName' => $this->notice->school->name,
            ]);
    }
}
