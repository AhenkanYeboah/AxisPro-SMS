<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TeacherVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code, public string $teacherName, public string $schoolName)
    {
    }

    public function build(): self
    {
        return $this->subject("Your {$this->schoolName} login verification code")
            ->view('emails.teacher-verification-code')
            ->with([
                'code' => $this->code,
                'teacherName' => $this->teacherName,
                'schoolName' => $this->schoolName,
            ]);
    }
}
