<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SalaryAdvance;
use App\Models\SalaryAdvanceRepayment;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalaryAdvanceController extends Controller
{
    public function index(Request $request)
    {
        $query = SalaryAdvance::query()
            ->with(['staff', 'repayments'])
            ->withSum('repayments as repaid_amount', 'amount')
            ->when($request->filled('search'), fn ($builder) => $builder->whereHas('staff', fn ($staffQuery) => $staffQuery
                ->where('first_name', 'like', '%' . $request->input('search') . '%')
                ->orWhere('last_name', 'like', '%' . $request->input('search') . '%')
                ->orWhere('phone', 'like', '%' . $request->input('search') . '%')
            ));

        $advances = $query
            ->orderByDesc('issued_on')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $totalAmount = (int) SalaryAdvance::sum('amount');
        $totalRepaid = (int) SalaryAdvanceRepayment::sum('amount');
        $totalBalance = max(0, $totalAmount - $totalRepaid);

        return view('staff.advances.index', compact(
            'advances', 'totalAmount', 'totalRepaid', 'totalBalance'
        ));
    }

    public function create()
    {
        $staff = Staff::active()->orderBy('last_name')->orderBy('first_name')->get();
        $advance = new SalaryAdvance(['issued_on' => today()]);

        return view('staff.advances.create', compact('advance', 'staff'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'staff_id' => ['required', 'integer', 'exists:staff,id'],
            'issued_on' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'integer', 'min:1'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $staff = Staff::active()->findOrFail($data['staff_id']);
        $advance = DB::transaction(function () use ($data, $staff) {
            $advance = $staff->salaryAdvances()->create($data);
            AuditLog::log('salary_advance_created', $advance, [], $advance->toArray());

            return $advance;
        });

        return redirect()
            ->route('staff.advances.show', $advance)
            ->with('success', 'Bon sur salaire enregistré.');
    }

    public function show(SalaryAdvance $advance)
    {
        $advance->load(['staff', 'repayments.recordedBy']);

        return view('staff.advances.show', compact('advance'));
    }

    public function edit(SalaryAdvance $advance)
    {
        $advance->load('repayments');
        $staff = Staff::withTrashed()
            ->where(function ($query) use ($advance) {
                $query->where('is_active', true)
                    ->orWhere('id', $advance->staff_id);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('staff.advances.edit', compact('advance', 'staff'));
    }

    public function update(Request $request, SalaryAdvance $advance)
    {
        $data = $request->validate([
            'staff_id' => ['required', 'integer', 'exists:staff,id'],
            'issued_on' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'integer', 'min:1'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if ((int) $data['staff_id'] !== (int) $advance->staff_id) {
            Staff::active()->findOrFail($data['staff_id']);
        }

        DB::transaction(function () use ($request, $data, $advance) {
            $lockedAdvance = SalaryAdvance::query()->lockForUpdate()->findOrFail($advance->id);
            $repaidAmount = (int) $lockedAdvance->repayments()->sum('amount');
            $request->validate([
                'amount' => ['required', 'integer', 'min:' . max(1, $repaidAmount)],
            ]);

            $oldValues = $lockedAdvance->only(['staff_id', 'issued_on', 'amount', 'purpose', 'notes']);
            $lockedAdvance->update($data);
            AuditLog::log('salary_advance_updated', $lockedAdvance, $oldValues, $lockedAdvance->fresh()->toArray());
        });

        return redirect()
            ->route('staff.advances.show', $advance)
            ->with('success', 'Bon sur salaire mis à jour.');
    }

    public function destroy(SalaryAdvance $advance)
    {
        if ($advance->repayments()->exists()) {
            return back()->with('error', 'Un bon ayant déjà des remboursements ne peut pas être supprimé.');
        }

        DB::transaction(function () use ($advance) {
            AuditLog::log('salary_advance_deleted', $advance, $advance->toArray());
            $advance->delete();
        });

        return redirect()
            ->route('staff.advances.index')
            ->with('success', 'Bon sur salaire supprimé.');
    }

    public function storeRepayment(Request $request, SalaryAdvance $advance)
    {
        $repayment = DB::transaction(function () use ($request, $advance) {
            $lockedAdvance = SalaryAdvance::query()->lockForUpdate()->findOrFail($advance->id);
            $balance = $lockedAdvance->balance;

            $data = $request->validate([
                'amount' => ['required', 'integer', 'min:1', 'max:' . $balance],
                'repaid_on' => ['required', 'date', 'before_or_equal:today'],
                'payment_method' => ['required', 'in:cash,bank_transfer,mobile_money,other'],
                'reference' => ['nullable', 'string', 'max:100'],
                'notes' => ['nullable', 'string', 'max:5000'],
            ]);

            $repayment = $lockedAdvance->repayments()->create([
                ...$data,
                'recorded_by' => Auth::id(),
            ]);
            AuditLog::log('salary_advance_repayment_created', $repayment, [], $repayment->toArray());

            return $repayment;
        });

        return redirect()
            ->route('staff.advances.show', $advance)
            ->with('success', 'Remboursement de ' . number_format($repayment->amount, 0, ',', ' ') . ' FCFA enregistré.');
    }
}
