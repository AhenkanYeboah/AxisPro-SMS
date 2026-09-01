<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentFee extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'fee_item_id', 'amount_pesewas', 'due_date', 'status',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeItem(): BelongsTo
    {
        return $this->belongsTo(FeeItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    public function amountPaidPesewas(): int
    {
        return (int) $this->payments()->sum('amount_pesewas');
    }

    public function balancePesewas(): int
    {
        return max(0, $this->amount_pesewas - $this->amountPaidPesewas());
    }

    /**
     * Recomputes and persists status from actual payments recorded against
     * this fee, rather than trusting a status flag someone forgot to
     * update. Called after every payment is recorded (see FeePayment
     * creation in AdminFeeController). 'waived' is never set here - that's
     * a deliberate admin action, not something payment totals should ever
     * override.
     */
    public function refreshStatus(): void
    {
        if ($this->status === 'waived') {
            return;
        }

        $paid = $this->amountPaidPesewas();

        $status = match (true) {
            $paid <= 0 => 'unpaid',
            $paid < $this->amount_pesewas => 'partially_paid',
            default => 'paid',
        };

        if ($status !== $this->status) {
            $this->update(['status' => $status]);
        }
    }
}
