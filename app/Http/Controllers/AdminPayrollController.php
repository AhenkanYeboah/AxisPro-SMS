<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Staff;
use App\Services\PayrollCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminPayrollController extends Controller
{
    public function index(): View
    {
        $runs = PayrollRun::withCount('payslips')
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get();

        return view('admin.payroll.index', compact('runs'));
    }

    public function create(): View
    {
        $activeStaffCount = Staff::where('is_active', true)->count();

        return view('admin.payroll.create', compact('activeStaffCount'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2020|max:2035',
        ]);

        $calculator = new PayrollCalculator(app('currentSchool')->id);

        try {
            $run = $calculator->createRunForSchool(
                app('currentSchool')->id,
                $data['period_month'],
                $data['period_year'],
                Auth::guard('admin')->id(),
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['period' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.payroll.show', $run)
            ->with('status', 'Payroll run generated - review it below before approving.');
    }

    public function show(PayrollRun $payrollRun): View
    {
        $payrollRun->load(['payslips.staff']);

        return view('admin.payroll.show', ['run' => $payrollRun]);
    }

    public function approve(PayrollRun $payrollRun): RedirectResponse
    {
        if ($payrollRun->status !== 'pending_approval') {
            return back()->withErrors(['status' => 'Only a run awaiting approval can be approved.']);
        }

        $payrollRun->update([
            'status' => 'approved',
            'approved_by_admin_id' => Auth::guard('admin')->id(),
            'approved_at' => now(),
        ]);

        return back()->with('status', 'Payroll run approved - ready to pay.');
    }

    public function markPaid(PayrollRun $payrollRun): RedirectResponse
    {
        if ($payrollRun->status !== 'approved') {
            return back()->withErrors(['status' => 'The run must be approved before it can be marked as paid.']);
        }

        $payrollRun->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return back()->with('status', 'Payroll run marked as paid.');
    }

    public function export(PayrollRun $payrollRun): Response
    {
        $calculator = new PayrollCalculator(app('currentSchool')->id);
        $csv = $calculator->exportBankCsv($payrollRun);

        $filename = "payroll-{$payrollRun->period_year}-{$payrollRun->period_month}-bank.csv";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    public function payslip(Payslip $payslip): View
    {
        $payslip->load('staff', 'payrollRun');

        return view('admin.payroll.payslip', compact('payslip'));
    }
}
