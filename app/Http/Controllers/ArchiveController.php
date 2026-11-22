<?php

namespace App\Http\Controllers;

use App\Models\ActivityAudit;
use App\Models\Announcement;
use App\Models\CtoRequest;
use App\Models\LeaveRequest;
use App\Models\LocatorSlip;
use App\Models\TravelOrder;
use App\Models\Saln;
use App\Models\PdsSnapshot;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use App\Listeners\AuditLogListener;

class ArchiveController extends Controller
{
    public function restore(string $category, int $record): RedirectResponse
    {
        $model = match ($category) {
            'announcements' => Announcement::class,
            'leave' => LeaveRequest::class,
            'locator-slips' => LocatorSlip::class,
            'travel-orders' => TravelOrder::class,
            'cto' => CtoRequest::class,
            'saln' => Saln::class,
            'pds' => PdsSnapshot::class,
            'logs' => ActivityAudit::class,
            'utility-leave-types' => \App\Models\LeaveType::class,
            'utility-salary-rates' => \App\Models\SalaryRate::class,
            default => $this->utilityGroup($category) ? \App\Models\UtilityOption::class : abort(404),
        };

        DB::transaction(function () use ($model, $category, $record) {
            $query = $model::onlyTrashed()->lockForUpdate();
            if ($group = $this->utilityGroup($category)) $query->where('group_key', $group);
            $archived = $query->findOrFail($record);
            $archived->restore();
            AuditLogListener::logActivity('Restore Archived Record', 'Archive',
                "Restored {$category} record #{$archived->id}.", auth()->user());
        });

        return redirect()->route('archive.index', ['tab' => $category])
            ->with('success', 'Record restored successfully.');
    }

    private function utilityGroup(string $category): ?string
    {
        $key = str_starts_with($category, 'utility-') ? substr($category, 8) : '';
        return isset(\App\Support\UtilityOptionRegistry::groups()[$key]) ? $key : null;
    }

    public function index(Request $request): View
    {
        $tabs = [
            'announcements' => 'Announcements',
            'leave' => 'Leave Applications',
            'locator-slips' => 'Locator Slips',
            'travel-orders' => 'Travel Authorities',
            'cto' => 'CTO/COC',
            'saln' => 'SALN',
            'pds' => 'PDS',
            'logs' => 'Logs Audit',
        ];

        $tabs['utility-leave-types'] = 'Leave Types';
        $tabs['utility-salary-rates'] = 'Salary Rate';
        foreach (\App\Support\UtilityOptionRegistry::groups() as $key => $meta) $tabs['utility-'.$key] = $meta['label'];
        $tab = $request->query('tab', array_key_first($tabs));
        abort_unless(is_string($tab) && isset($tabs[$tab]), 404);

        $query = match ($tab) {
            'announcements' => Announcement::onlyTrashed()->with('author.employee'),
            'leave' => LeaveRequest::onlyTrashed()->with(['employee' => fn ($query) => $query->withTrashed(), 'leaveType']),
            'travel-orders' => TravelOrder::onlyTrashed()->with(['employee' => fn ($query) => $query->withTrashed()]),
            'locator-slips' => LocatorSlip::onlyTrashed()->with(['employee' => fn ($query) => $query->withTrashed()]),
            'cto' => CtoRequest::onlyTrashed()->with(['employee' => fn ($query) => $query->withTrashed()]),
            'saln' => Saln::onlyTrashed()->with(['employee' => fn ($query) => $query->withTrashed()]),
            'pds' => PdsSnapshot::onlyTrashed()->with(['employee' => fn ($query) => $query->withTrashed()]),
            'logs' => ActivityAudit::onlyTrashed(),
            'utility-leave-types' => \App\Models\LeaveType::onlyTrashed(),
            'utility-salary-rates' => \App\Models\SalaryRate::onlyTrashed(),
            default => \App\Models\UtilityOption::onlyTrashed()->where('group_key', $this->utilityGroup($tab)),
        };
        $records = $query->latest('deleted_at')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('archive', compact('tabs', 'tab', 'records'));
    }
}
