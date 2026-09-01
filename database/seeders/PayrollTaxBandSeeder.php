<?php

namespace Database\Seeders;

use App\Models\PayrollTaxBand;
use Illuminate\Database\Seeder;

class PayrollTaxBandSeeder extends Seeder
{
    /**
     * Ghana GRA PAYE annual bands for 2026 (Income Tax Act 2015, Act 896,
     * as amended). Cross-checked against multiple current sources at
     * build time - first GHS 5,880/year (GHS 490/month) tax-free, then
     * six further progressive bands up to 35%. VERIFY against a current
     * GRA publication before relying on this for real payroll - bands
     * are revised periodically and this is not tax advice.
     *
     * Amounts are annual, in pesewas (GHS x 100).
     */
    public function run(): void
    {
        $bands = [
            // order, lower (exclusive), upper (inclusive, null = no ceiling), rate %
            [1,       0,   588_000,  0.0],   // first GHS 5,880/yr
            [2, 588_000,   720_000,  5.0],   // next GHS 1,320
            [3, 720_000,   876_000, 10.0],   // next GHS 1,560
            [4, 876_000, 4_676_000, 17.5],   // next GHS 38,000
            [5, 4_676_000, 23_876_000, 25.0], // next GHS 192,000
            [6, 23_876_000, 60_500_000, 30.0], // next GHS 366,240
            [7, 60_500_000, null, 35.0],      // above GHS 605,000
        ];

        foreach ($bands as [$order, $lower, $upper, $rate]) {
            PayrollTaxBand::updateOrCreate(
                ['school_id' => null, 'effective_year' => 2026, 'band_order' => $order],
                [
                    'annual_lower_bound_pesewas' => $lower,
                    'annual_upper_bound_pesewas' => $upper,
                    'rate_percentage' => $rate,
                ]
            );
        }
    }
}
