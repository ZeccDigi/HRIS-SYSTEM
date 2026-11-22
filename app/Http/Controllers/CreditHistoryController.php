<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\CtoRequest;
use App\Models\LeaveCreditTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CreditHistoryController extends Controller
{
    private function requireHr(Request $request): void
    {
        abort_unless(in_array(strtolower($request->user()->role), ['admin', 'hrstaff'], true), 403);
    }

    public function leave(Request $request, Employee $employee): JsonResponse
    {
        $this->requireHr($request);
        $data = $request->validate(['leave_type_id' => 'required|integer|exists:leave_types,id', 'year' => 'required|integer|min:2000|max:2100']);
        return response()->json(app(\App\Services\LeaveCreditHistoryService::class)->history($employee, (int) $data['leave_type_id'], (int) $data['year']));
    }
    public function employeeCoc(Request $request, Employee $employee): JsonResponse
    {
        $this->requireHr($request);
        return $this->coc($request, $employee);
    }

    public function myCoc(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403);
        return $this->coc($request, $employee);
    }

    private function coc(Request $request, Employee $employee): JsonResponse
    {
        $data = $request->validate(['year' => 'required|integer|min:2000|max:2100']);
        $page = CtoRequest::withTrashed()->with(['recordedBy.employee', 'regionalDirector'])
            ->where('employee_id', $employee->id)->where('status', 'approved')
            ->whereNotNull('cto_balance_after')->whereYear('date_start', $data['year'])
            ->orderByDesc('credits_posted_at')->orderByDesc('id')->paginate(10);
        return response()->json([
            'rows' => $page->getCollection()->map(function ($entry) {
                $actor = $entry->credit_source === 'hr_direct' ? $entry->recordedBy?->employee : $entry->regionalDirector;
                $actorName = $actor ? trim(implode(' ', array_filter([$actor->firstname, $actor->middlename, $actor->lastname]))) : ($entry->recordedBy?->name ?? '--');
                $schedule = $entry->date_start?->format('M d, Y') ?? '--';
                if ($entry->date_end && $entry->date_start?->toDateString() !== $entry->date_end->toDateString()) $schedule .= ' – '.$entry->date_end->format('M d, Y');
                return ['id' => $entry->id, 'date' => $entry->credits_posted_at?->copy()->timezone('Asia/Manila')->format('M d, Y · h:i:s A') ?? 'Not recorded',
                    'schedule' => ($entry->type === 'earn' ? 'COC: ' : 'CTO: ').$schedule,
                    'operation' => $entry->type === 'earn' ? 'add' : 'deduct', 'amount' => (float) $entry->hours,
                    'balance' => (float) $entry->cto_balance_after, 'staff' => $actorName, 'position' => $actor?->position ?? '',
                    'remarks' => $entry->reference.' · '.($entry->credit_source === 'hr_direct' ? 'HR credit addition' : ($entry->type === 'earn' ? 'Approved COC' : 'Approved CTO'))];
            })->values(), 'page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(),
        ]);
    }
}
