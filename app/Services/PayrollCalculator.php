<?php

namespace App\Services;

use App\Models\PayrollRun;
use App\Models\PayrollSetting;
use App\Models\PayrollTaxBand;
use App\Models\Payslip;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

/**
 * Computes SSNIT and PAYE for one staff member for one pay period, and
 * assembles the full payslip breakdown (allowances, deductions, net pay).
 *
 * IMPORTANT: the seeded PAYE bands and the default SSNIT rates below are
 * based on published 2026 GRA/SSNIT figures at the time this was built,
 * but tax law changes and different sources don't always agree on the
 * exact SSNIT insurable-earnings ceiling. Before running payroll for real
 * money, verify the rates in payroll_settings and the bands in
 * payroll_tax_bands against a current GRA/SSNIT notice or your
 * accountant - this is not tax advice.
 */
class PayrollCalculator
{
    public function __construct(protected int $schoolId)
    {
    }

    /**
     * Calculate a full payslip breakdown for a staff member for a given
     * period. Returns pesewas amounts plus a line-item breakdown array
     * suitable for storing on the payslip.
     */
    public function calculateForStaff(Staff $staff, int $periodMonth, int $periodYear): array
    {
        $structure = $staff->currentSalaryStructure();
        $basicSalaryPesewas = $structure?->basic_salary_pesewas ?? 0;

        $payItems = $staff->payItems()->where('is_active', true)->get();

        $allowanceItems = $payItems->where('type', 'allowance');
        $deductionItems = $payItems->where('type', 'deduction');

        $allowancesPesewas = $allowanceItems->sum(fn ($item) => $item->amountFor($basicSalaryPesewas));
        $otherDeductionsPesewas = $deductionItems->sum(fn ($item) => $item->amountFor($basicSalaryPesewas));

        $grossPayPesewas = $basicSalaryPesewas + $allowancesPesewas;

        $settings = PayrollSetting::forSchool($this->schoolId);

        // SSNIT is calculated on basic salary, capped at the insurable
        // earnings ceiling if one is configured.
        $ssnitBasePesewas = $basicSalaryPesewas;
        if ($settings->ssnit_ceiling_pesewas !== null) {
            $ssnitBasePesewas = min($ssnitBasePesewas, $settings->ssnit_ceiling_pesewas);
        }

        $ssnitEmployeePesewas = (int) round($ssnitBasePesewas * ((float) $settings->ssnit_employee_rate / 100));
        $ssnitEmployerPesewas = (int) round($ssnitBasePesewas * ((float) $settings->ssnit_employer_rate / 100));

        // Taxable income = gross pay minus the employee's own SSNIT
        // contribution (standard treatment - SSNIT employee contributions
        // are deducted before PAYE is applied).
        $taxableMonthlyPesewas = max(0, $grossPayPesewas - $ssnitEmployeePesewas);

        $payePesewas = $this->calculatePaye($taxableMonthlyPesewas, $periodYear);

        $netPayPesewas = $grossPayPesewas - $ssnitEmployeePesewas - $payePesewas - $otherDeductionsPesewas;

        return [
            'basic_salary_pesewas' => $basicSalaryPesewas,
            'allowances_pesewas' => $allowancesPesewas,
            'gross_pay_pesewas' => $grossPayPesewas,
            'ssnit_employee_pesewas' => $ssnitEmployeePesewas,
            'ssnit_employer_pesewas' => $ssnitEmployerPesewas,
            'paye_pesewas' => $payePesewas,
            'other_deductions_pesewas' => $otherDeductionsPesewas,
            'net_pay_pesewas' => $netPayPesewas,
            'breakdown' => [
                'allowances' => $allowanceItems->map(fn ($item) => [
                    'name' => $item->name,
                    'amount_pesewas' => $item->amountFor($basicSalaryPesewas),
                ])->values()->all(),
                'deductions' => $deductionItems->map(fn ($item) => [
                    'name' => $item->name,
                    'amount_pesewas' => $item->amountFor($basicSalaryPesewas),
                ])->values()->all(),
            ],
        ];
    }

    /**
     * Compute and persist a full payroll run: one PayrollRun plus one
     * Payslip per active staff member. Throws if a non-cancelled run
     * already exists for that school/period so a run is never silently
     * duplicated or overwritten.
     */
    public function createRunForSchool(int $schoolId, int $periodMonth, int $periodYear, ?int $preparedByAdminId = null): PayrollRun
    {
        $existing = PayrollRun::where('school_id', $schoolId)
            ->where('period_month', $periodMonth)
            ->where('period_year', $periodYear)
            ->first();

        if ($existing && $existing->status !== 'cancelled') {
            throw new \RuntimeException(
                "A payroll run for {$periodMonth}/{$periodYear} already exists with status \"{$existing->status}\"."
            );
        }

        return DB::transaction(function () use ($schoolId, $periodMonth, $periodYear, $preparedByAdminId) {
            $run = PayrollRun::create([
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                // Skips a separate "draft" preview stage for v1 - the run is
                // computed and saved in one step, then reviewed here before
                // approval. A true calculate-without-saving preview is a
                // reasonable next iteration but adds real complexity for now.
                'status' => 'pending_approval',
                'prepared_by_admin_id' => $preparedByAdminId,
            ]);

            $totals = [
                'gross' => 0, 'ssnit_employee' => 0, 'paye' => 0, 'other_deductions' => 0, 'net' => 0,
            ];

            $staffMembers = Staff::where('school_id', $schoolId)->where('is_active', true)->get();

            foreach ($staffMembers as $staff) {
                $calc = $this->calculateForStaff($staff, $periodMonth, $periodYear);

                Payslip::create([
                    'payroll_run_id' => $run->id,
                    'staff_id' => $staff->id,
                    'basic_salary_pesewas' => $calc['basic_salary_pesewas'],
                    'allowances_pesewas' => $calc['allowances_pesewas'],
                    'gross_pay_pesewas' => $calc['gross_pay_pesewas'],
                    'ssnit_employee_pesewas' => $calc['ssnit_employee_pesewas'],
                    'ssnit_employer_pesewas' => $calc['ssnit_employer_pesewas'],
                    'paye_pesewas' => $calc['paye_pesewas'],
                    'other_deductions_pesewas' => $calc['other_deductions_pesewas'],
                    'net_pay_pesewas' => $calc['net_pay_pesewas'],
                    'breakdown' => $calc['breakdown'],
                ]);

                $totals['gross'] += $calc['gross_pay_pesewas'];
                $totals['ssnit_employee'] += $calc['ssnit_employee_pesewas'];
                $totals['paye'] += $calc['paye_pesewas'];
                $totals['other_deductions'] += $calc['other_deductions_pesewas'];
                $totals['net'] += $calc['net_pay_pesewas'];
            }

            $run->update([
                'total_gross_pesewas' => $totals['gross'],
                'total_ssnit_employee_pesewas' => $totals['ssnit_employee'],
                'total_paye_pesewas' => $totals['paye'],
                'total_other_deductions_pesewas' => $totals['other_deductions'],
                'total_net_pesewas' => $totals['net'],
            ]);

            return $run->load('payslips.staff');
        });
    }

    /**
     * Simple CSV of net pay per staff member, formatted for a bank salary
     * upload. Column layout is a reasonable generic starting point - most
     * Ghanaian banks want their own specific template, so this will likely
     * need adapting to whichever bank the school actually uses.
     */
    public function exportBankCsv(PayrollRun $run): string
    {
        $lines = ['Staff Name,Staff No,Bank,Account Number,Net Pay (GHS)'];

        foreach ($run->payslips()->with('staff')->get() as $payslip) {
            $staff = $payslip->staff;
            $lines[] = implode(',', [
                '"'.str_replace('"', '""', $staff->full_name).'"',
                $staff->staff_no,
                '"'.str_replace('"', '""', (string) $staff->bank_name).'"',
                $staff->bank_account_number,
                number_format($payslip->net_pay_pesewas / 100, 2, '.', ''),
            ]);
        }

        return implode("\n", $lines);
    }

    /**
     * Apply the progressive PAYE bands to one month of taxable income.
     * Bands are stored as annual thresholds, so we annualize, tax it
     * band-by-band, then divide back down to a monthly figure.
     */
    protected function calculatePaye(int $taxableMonthlyPesewas, int $periodYear): int
    {
        $annualTaxablePesewas = $taxableMonthlyPesewas * 12;

        $bands = PayrollTaxBand::forSchoolAndYear($this->schoolId, $periodYear);

        if ($bands->isEmpty()) {
            // No bands configured for this year - fail safe to zero tax
            // rather than guessing, so the shortfall is obvious and gets
            // fixed by seeding/configuring bands for the year in question.
            return 0;
        }

        $annualTaxPesewas = 0;

        foreach ($bands as $band) {
            $lower = $band->annual_lower_bound_pesewas;
            $upper = $band->annual_upper_bound_pesewas; // null = no ceiling

            if ($annualTaxablePesewas <= $lower) {
                break;
            }

            $taxableInBand = $upper !== null
                ? min($annualTaxablePesewas, $upper) - $lower
                : $annualTaxablePesewas - $lower;

            $taxableInBand = max(0, $taxableInBand);

            $annualTaxPesewas += (int) round($taxableInBand * ((float) $band->rate_percentage / 100));
        }

        return (int) round($annualTaxPesewas / 12);
    }
}
