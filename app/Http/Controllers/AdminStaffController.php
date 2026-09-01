<?php

namespace App\Http\Controllers;

use App\Models\SalaryStructure;
use App\Models\Staff;
use App\Models\StaffPayItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * HR side of payroll: staff records and their current basic salary /
 * recurring allowances & deductions. Actually running payroll each period
 * is AdminPayrollController - this controller only manages who gets paid
 * and how much their standing pay looks like.
 */
class AdminStaffController extends Controller
{
    public function index(): View
    {
        $staff = Staff::with('currentSalaryStructure')
            ->orderBy('full_name')
            ->get();

        return view('admin.staff.index', compact('staff'));
    }

    public function create(): View
    {
        return view('admin.staff.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('currentSchool')->id;

        $data = $request->validate([
            'staff_no' => [
                'required', 'string', 'max:30',
                Rule::unique('staff', 'staff_no')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'full_name' => 'required|string|max:150',
            'position' => 'required|string|max:100',
            'department' => 'nullable|string|max:100',
            'employment_type' => 'required|in:full_time,part_time,contract,casual',
            'date_joined' => 'nullable|date',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:150',
            'mobile_money_provider' => 'nullable|string|max:50',
            'mobile_money_number' => 'nullable|string|max:30',
            'basic_ghs' => 'required|numeric|min:0', // entered in GHS, converted below - same convention as FeeItem
            'allowances' => 'nullable|array',
            'allowances.*.name' => 'required_with:allowances|string|max:100',
            'allowances.*.amount_ghs' => 'required_with:allowances|numeric|min:0',
        ]);

        DB::transaction(function () use ($data) {
            $staff = Staff::create([
                'staff_no' => $data['staff_no'],
                'full_name' => $data['full_name'],
                'position' => $data['position'],
                'department' => $data['department'] ?? null,
                'employment_type' => $data['employment_type'],
                'date_joined' => $data['date_joined'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'bank_account_name' => $data['bank_account_name'] ?? null,
                'mobile_money_provider' => $data['mobile_money_provider'] ?? null,
                'mobile_money_number' => $data['mobile_money_number'] ?? null,
                'is_active' => true,
            ]);

            SalaryStructure::create([
                'staff_id' => $staff->id,
                'basic_salary_pesewas' => (int) round($data['basic_ghs'] * 100),
                'effective_from' => now()->toDateString(),
                'created_by_admin_id' => Auth::guard('admin')->id(),
            ]);

            foreach ($data['allowances'] ?? [] as $allowance) {
                if (empty($allowance['name'])) {
                    continue;
                }

                StaffPayItem::create([
                    'staff_id' => $staff->id,
                    'name' => $allowance['name'],
                    'type' => 'allowance',
                    'amount_pesewas' => (int) round($allowance['amount_ghs'] * 100),
                    'is_recurring' => true,
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('admin.staff.index')
            ->with('status', "Staff member {$data['full_name']} added.");
    }

    public function edit(Staff $staff): View
    {
        $staff->load('currentSalaryStructure');
        $allowances = $staff->payItems()->where('type', 'allowance')->where('is_active', true)->get();
        $deductions = $staff->payItems()->where('type', 'deduction')->where('is_active', true)->get();

        return view('admin.staff.edit', compact('staff', 'allowances', 'deductions'));
    }

    public function update(Request $request, Staff $staff): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:150',
            'position' => 'required|string|max:100',
            'department' => 'nullable|string|max:100',
            'employment_type' => 'required|in:full_time,part_time,contract,casual',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:150',
            'mobile_money_provider' => 'nullable|string|max:50',
            'mobile_money_number' => 'nullable|string|max:30',
            'basic_ghs' => 'required|numeric|min:0',
            'allowances' => 'nullable|array',
            'allowances.*.name' => 'required_with:allowances|string|max:100',
            'allowances.*.amount_ghs' => 'required_with:allowances|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);

        DB::transaction(function () use ($data, $staff) {
            $staff->update([
                'full_name' => $data['full_name'],
                'position' => $data['position'],
                'department' => $data['department'] ?? null,
                'employment_type' => $data['employment_type'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'bank_account_name' => $data['bank_account_name'] ?? null,
                'mobile_money_provider' => $data['mobile_money_provider'] ?? null,
                'mobile_money_number' => $data['mobile_money_number'] ?? null,
                'is_active' => $data['is_active'],
                'date_left' => ! $data['is_active'] && $staff->is_active ? now()->toDateString() : $staff->date_left,
            ]);

            $newBasicPesewas = (int) round($data['basic_ghs'] * 100);
            $current = $staff->currentSalaryStructure();

            // Only version the salary if it actually changed - avoids a
            // new history row every time an admin re-saves the form
            // without touching the basic salary.
            if (! $current || $current->basic_salary_pesewas !== $newBasicPesewas) {
                $current?->update(['effective_to' => now()->toDateString()]);

                SalaryStructure::create([
                    'staff_id' => $staff->id,
                    'basic_salary_pesewas' => $newBasicPesewas,
                    'effective_from' => now()->toDateString(),
                    'created_by_admin_id' => Auth::guard('admin')->id(),
                ]);
            }

            // Allowances are replaced wholesale on each save, same as the
            // basic salary form - simplest mental model for v1. Deductions
            // (e.g. an ongoing loan repayment) are left alone here since
            // this form doesn't expose them; manage those separately if
            // you need finer control than "replace all allowances".
            $staff->payItems()->where('type', 'allowance')->update(['is_active' => false]);

            foreach ($data['allowances'] ?? [] as $allowance) {
                if (empty($allowance['name'])) {
                    continue;
                }

                StaffPayItem::create([
                    'staff_id' => $staff->id,
                    'name' => $allowance['name'],
                    'type' => 'allowance',
                    'amount_pesewas' => (int) round($allowance['amount_ghs'] * 100),
                    'is_recurring' => true,
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('admin.staff.index')->with('status', 'Staff record updated.');
    }
}
