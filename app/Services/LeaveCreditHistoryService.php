<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveCreditTransaction;
use App\Models\User;

class LeaveCreditHistoryService
{
    public function history(Employee $employee, int $typeId, int $year): array
    {
        $query = LeaveCreditTransaction::where('employee_id', $employee->id)->where('leave_type_id', $typeId);
        $years = (clone $query)->get()->map(fn ($entry) => (int) ($entry->credit_year ?? $entry->metadata['year'] ?? $entry->effective_date?->year))
            ->push((int) now()->year)->filter()->unique()->sortDesc()->values();
        $page = $query->with(['leaveRequest' => fn ($query) => $query->withTrashed()->with('regionalDirector')])
            ->where(function ($query) use ($year) {
                $query->where('credit_year', $year)->orWhere(function ($query) use ($year) {
                    $query->whereNull('credit_year')->where(function ($query) use ($year) {
                        $query->where('metadata->year', $year)->orWhere(fn ($query) => $query->whereNull('metadata->year')->whereYear('effective_date', $year));
                    });
                });
            })->latest('created_at')->latest('id')->paginate(10);
        $staff = User::with('employee')->whereIn('id', $page->getCollection()->map(fn ($entry) => $entry->metadata['updated_by'] ?? null)->filter())->get()->keyBy('id');
        return ['years' => $years, 'year' => $year, 'rows' => $page->getCollection()->map(function ($entry) use ($staff) {
            $actor = $staff->get($entry->metadata['updated_by'] ?? null);
            $request = $entry->leaveRequest;
            $approver = $request?->regionalDirector;
            $schedule = $entry->effective_date?->format('M d, Y') ?? '--';
            if ($request && !$request->is_monetization && $request->start_date) {
                $schedule = $request->start_date->format('M d, Y');
                if ($request->end_date && !$request->start_date->isSameDay($request->end_date)) $schedule .= ' – '.$request->end_date->format('M d, Y');
            }
            $label = match ($entry->type) {
                'accrual' => (float) $entry->amount < 0 ? 'Earning correction' : 'Monthly earning',
                'entitlement' => 'Entitlement', 'usage' => 'Leave deduction', 'monetization' => 'Monetization',
                'carryover' => 'Carryover', default => (float) $entry->amount < 0 ? 'Deduction' : 'Addition',
            };
            return ['id' => $entry->id, 'date' => $entry->created_at->copy()->timezone('Asia/Manila')->format('M d, Y · h:i:s A'),
                'schedule' => $schedule, 'label' => $label,
                'operation' => $entry->type === 'carryover' ? 'carryover' : ((float) $entry->amount < 0 ? 'deduct' : 'add'),
                'amount' => abs((float) $entry->amount), 'balance' => (float) $entry->balance_after,
                'staff' => $actor?->name ?? ($approver ? trim($approver->firstname.' '.$approver->lastname) : ($entry->type === 'adjustment' ? 'HR' : 'System')),
                'position' => $actor?->employee?->position ?? $approver?->position ?? '',
                'remarks' => $entry->description === 'HR leave credit adjustment' ? '' : $entry->description];
        })->values(), 'page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()];
    }
}
