<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Student-side "Fees" tab - read-only in Phase 1. Shows the logged-in
 * student's own fees and payment history; no online payment yet (see
 * expansion-design-doc.md Phase 2 for that).
 */
class StudentDashboardFeeController extends Controller
{
    public function index(): View
    {
        $student = Auth::guard('student')->user();

        $studentFees = $student->studentFees()
            ->with(['feeItem', 'payments'])
            ->orderByDesc('created_at')
            ->get();

        $totals = [
            'billed' => $studentFees->sum('amount_pesewas'),
            'paid' => $studentFees->sum(fn ($fee) => $fee->amountPaidPesewas()),
        ];
        $totals['balance'] = $totals['billed'] - $totals['paid'];

        return view('student.fees.index', compact('studentFees', 'totals'));
    }
}
