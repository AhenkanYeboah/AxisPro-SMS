<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class SchoolActivity extends Model
{
    use BelongsToSchool;

    protected $table = 'school_activities';

    protected $fillable = [
        'school_id', 'title', 'description', 'activity_date', 'category',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
        ];
    }
}
