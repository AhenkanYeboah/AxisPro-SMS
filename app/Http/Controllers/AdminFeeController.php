<?php

namespace App\Http\Controllers;

use App\Mail\StudentFeeAssignedMail;
use App\Models\FeeItem;
use App\Models\FeePayment;
use App\Models\Student;
use App\Models\StudentFee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * School-admin-facing fees & bills: define chargeable fee items, assign
 * them to students (individually or by class), and record payments as they
 * come in offline (cash/mobile money/bank). Phase 1 only - see
 * expansion-design-doc.md for the Phase 2 online-payment plan.
 *
 * This is NOT platform subscription billing (school paying RCA-SaaS) - see
 * Platform\PlatformBillingController for that.
 */
class AdminFeeController extends Controller
{
    public function index(): View
    {
        $feeItems = FeeItem::withCount('studentFees')
            ->orderByDesc('created_at')
            ->get();

        $studentFees = StudentFee::with(['student', 'feeItem'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $summary = [
            'total_billed' => (int) StudentFee::sum('amount_pesewas'),
            'total_collected' => (int) FeePayment::sum('amount_pesewas'),
            'unpaid_count' => StudentFee::where('status', 'unpaid')->count(),
            'partially_paid_count' => StudentFee::where('status', 'partially_paid')->count(),
        ];

        return view('admin.fees.index', compact('feeItems', 'studentFees', 'summary'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.01', // entered in GHS, converted below
            'class' => 'nullable|string|max:50',
            'frequency' => 'required|in:one_off,termly,monthly',
            'term' => 'nullable|string|max:20',
            'academic_year' => 'nullable|string|max:20',
        ]);

        FeeItem::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'amount_pesewas' => (int) round($data['amount'] * 100),
            'class' => $data['class'] ?: null, // blank = all classes
            'frequency' => $data['frequency'],
            'term' => $data['term'] ?? null,
            'academic_year' => $data['academic_year'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('admin.fees.index')->with('status', 'Fee item created. Use "Assign" to bill it to students.');
    }

    public function destroy(FeeItem $feeItem): RedirectResponse
    {
        // Soft-disable rather than delete outright if it's already been
        // assigned to anyone - deleting would cascade and wipe billing
        // history for students who were already charged.
        if ($feeItem->studentFees()->exists()) {
            $feeItem->update(['is_active' => false]);

            return redirect()->route('admin.fees.index')->with('status', 'Fee item has already been assigned to students, so it was deactivated instead of deleted (billing history is preserved).');
        }

        $feeItem->delete();

        return redirect()->route('admin.fees.index')->with('status', 'Fee item deleted.');
    }

    /**
     * Assigns a fee item to students - either everyone in one class, or
     * every currently-active student if no class is specified. Skips
     * students who already have this exact fee item assigned (no
     * duplicate billing on repeat clicks).
     */
    public function assign(Request $request, FeeItem $feeItem): RedirectResponse
    {
        $data = $request->validate([
            'class' => 'nullable|string|max:50',
            'due_date' => 'nullable|date',
        ]);

        $query = Student::where('status', 'active');

        if (! empty($data['class'])) {
            $query->where('class', $data['class']);
        } elseif ($feeItem->class) {
            // No class override given - fall back to the fee item's own
            // class scope, if it has one.
            $query->where('class', $feeItem->class);
        }

        $alreadyAssignedIds = StudentFee::where('fee_item_id', $feeItem->id)
            ->pluck('student_id');

        $students = $query->whereNotIn('id', $alreadyAssignedIds)->get();

        if ($students->isEmpty()) {
            return back()->with('status', 'No new students to assign - everyone matching this scope already has this fee.');
        }

        foreach ($students as $student) {
            $studentFee = StudentFee::create([
                'student_id' => $student->id,
                'fee_item_id' => $feeItem->id,
                'amount_pesewas' => $feeItem->amount_pesewas,
                'due_date' => $data['due_date'] ?? null,
                'status' => 'unpaid',
            ]);

            // Best-effort notify - a mail failure for one parent shouldn't
            // block assigning the fee to the rest of the class.
            $this->notifyFeeAssigned($studentFee);
        }

        return redirect()->route('admin.fees.index')->with('status', "Assigned \"{$feeItem->name}\" to {$students->count()} student(s).");
    }

    private function notifyFeeAssigned(StudentFee $studentFee): void
    {
        $email = $studentFee->student->contactEmail();

        if (! $email) {
            return;
        }

        try {
            Mail::to($email)->send(new StudentFeeAssignedMail($studentFee));
        } catch (\Throwable $e) {
            // Swallow - fee assignment itself already succeeded and is the
            // thing that matters; email delivery is a courtesy on top.
            report($e);
        }
    }

    public function showStudentFee(StudentFee $studentFee): View
    {
        $studentFee->load(['student', 'feeItem', 'payments.recordedBy']);

        return view('admin.fees.show', compact('studentFee'));
    }

    /**
     * Records a payment received offline (cash/mobile money/bank) against
     * a student's fee. Supports partial payments - refreshStatus() derives
     * unpaid/partially_paid/paid from the running total rather than
     * trusting a manually-set status.
     */
    public function recordPayment(Request $request, StudentFee $studentFee): RedirectResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,mobile_money,bank',
            'reference' => 'nullable|string|max:100',
            'paid_at' => 'nullable|date',
        ]);

        $amountPesewas = (int) round($data['amount'] * 100);
        $balance = $studentFee->balancePesewas();

        if ($amountPesewas > $balance) {
            return back()->withErrors([
                'amount' => 'Payment amount (GHS ' . number_format($amountPesewas / 100, 2) . ') exceeds the outstanding balance (GHS ' . number_format($balance / 100, 2) . ').',
            ])->withInput();
        }

        FeePayment::create([
            'student_fee_id' => $studentFee->id,
            'amount_pesewas' => $amountPesewas,
            'method' => $data['method'],
            'reference' => $data['reference'] ?? null,
            'recorded_by_admin_id' => Auth::guard('admin')->id(),
            'paid_at' => $data['paid_at'] ?? now(),
        ]);

        $studentFee->refreshStatus();

        return back()->with('status', 'Payment recorded.');
    }

    public function waive(StudentFee $studentFee): RedirectResponse
    {
        $studentFee->update(['status' => 'waived']);

        return back()->with('status', 'Fee waived.');
    }
}
