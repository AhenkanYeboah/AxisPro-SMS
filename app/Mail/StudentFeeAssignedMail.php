<?php

namespace App\Mail;

use App\Models\StudentFee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentFeeAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public StudentFee $studentFee)
    {
    }

    public function build(): self
    {
        $student = $this->studentFee->student;
        $schoolName = $student->school->name;

        return $this->subject("{$schoolName}: New fee for {$student->fullName()}")
            ->view('emails.student-fee-assigned')
            ->with([
                'studentName' => $student->fullName(),
                'feeName' => $this->studentFee->feeItem->name,
                'amount' => number_format($this->studentFee->amount_pesewas / 100, 2),
                'dueDate' => $this->studentFee->due_date,
                'schoolName' => $schoolName,
            ]);
    }
}
