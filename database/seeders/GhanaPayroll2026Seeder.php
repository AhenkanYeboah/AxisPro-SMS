<?php

namespace Database\Seeders;

use App\Models\PayrollTaxBand;
use App\Models\PayrollSetting;
use Illuminate\Database\Seeder;

class GhanaPayroll2026Seeder extends Seeder
{
    public function run(): void
    {
        // Ghana 2024/2026 PAYE monthly bands (GRA) - stored as ANNUAL pesewas for your schema
        // Monthly: 0-490 @0%, 490-600 @5%, 600-730 @10%, 730-3896.67 @17.5%, 3896.67-47428 @25%, >47428 @30%
        // Annual = Monthly * 12
        $monthlyBands = [
            ['lower' => 0,       'upper' => 49000,   'rate' => 0],
            ['lower' => 49000,   'upper' => 60000,   'rate' => 5],
            ['lower' => 60000,   'upper' => 73000,   'rate' => 10],
            ['lower' => 73000,   'upper' => 389667,  'rate' => 17.5],
            ['lower' => 389667,  'upper' => 4742800, 'rate' => 25],
            ['lower' => 4742800, 'upper' => null,    'rate' => 30],
        ];

        foreach ($monthlyBands as $i => $b) {
            PayrollTaxBand::updateOrCreate(
                ['school_id' => null, 'effective_year' => 2026, 'band_order' => $i+1],
                [
                    'annual_lower_bound_pesewas' => $b['lower'] * 12,
                    'annual_upper_bound_pesewas' => $b['upper'] ? $b['upper'] * 12 : null,
                    'rate_percentage' => $b['rate'],
                ]
            );
            // Also seed for 2024 and 2025 for backward compat
            foreach ([2024,2025] as $year) {
                PayrollTaxBand::updateOrCreate(
                    ['school_id' => null, 'effective_year' => $year, 'band_order' => $i+1],
                    [
                        'annual_lower_bound_pesewas' => $b['lower'] * 12,
                        'annual_upper_bound_pesewas' => $b['upper'] ? $b['upper'] * 12 : null,
                        'rate_percentage' => $b['rate'],
                    ]
                );
            }
        }

        // Platform default settings - schools will get their own row via firstOrCreate
        // 2026 SSNIT ceiling - VERIFY with SSNIT notice: currently no strict monthly ceiling but Tier1 has max? Keeping null = uncapped for v1
    }
}
