<?php

namespace App\Http\Controllers;

use App\Models\ActivityAudit;
use App\Models\Announcement;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArchiveController extends Controller
{
    public function index(Request $request): View
    {
        $tabs = ['announcements' => 'Announcements', 'logs' => 'Logs Audit'];
        if (LeaveRequest::onlyTrashed()->exists()) {
            $tabs = ['announcements' => 'Announcements', 'leave' => 'Archived Leave Requests', 'logs' => 'Logs Audit'];
        }
        $tab = $request->query('tab', 'announcements');
        abort_unless(is_string($tab) && isset($tabs[$tab]), 404);

        $query = match ($tab) {
            'announcements' => Announcement::onlyTrashed()->with('author.employee'),
            'leave' => LeaveRequest::onlyTrashed()->with(['employee' => fn ($query) => $query->withTrashed(), 'leaveType']),
            'logs' => ActivityAudit::onlyTrashed(),
        };
        $records = $query->latest('deleted_at')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('archive', compact('tabs', 'tab', 'records'));
    }
}
