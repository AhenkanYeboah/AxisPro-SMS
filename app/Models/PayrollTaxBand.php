<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollTaxBand extends Model
{
    protected $fillable = [
        'school_id', 'effective_year', 'band_order',
        'annual_lower_bound_pesewas', 'annual_upper_bound_pesewas', 'rate_percentage',
    ];

    protected function casts(): array
    {
        return [
            'rate_percentage' => 'decimal:2',
        ];
    }

    /**
     * The bands to use for a school in a given year: that school's own
     * override rows if it has any for that year, otherwise the platform
     * default rows (school_id null).
     */
    public static function forSchoolAndYear(int $schoolId, int $year)
    {
        $override = static::where('school_id', $schoolId)
            ->where('effective_year', $year)
            ->orderBy('band_order')
            ->get();

        if ($override->isNotEmpty()) {
            return $override;
        }

        return static::whereNull('school_id')
            ->where('effective_year', $year)
            ->orderBy('band_order')
            ->get();
    }
}
