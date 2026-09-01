<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'reference', 'plan', 'amount_pesewas', 'currency',
        'status', 'paystack_transaction_id', 'gateway_response',
    ];

    protected function casts(): array
    {
        return [
            'gateway_response' => 'array',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
