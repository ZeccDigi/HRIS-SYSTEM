<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\CtoRequest;
use App\Models\TravelOrder;
use App\Models\PdsTraining;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupportingDocumentController extends Controller
{
    public function __invoke(Request $request)
    {
        $path = $request->query('path');
        abort_unless(is_string($path) && strlen($path) <= 512 && preg_match('~^(leave-attachments|cto-attachments|travel-order-attachments|travel-accomplishment-reports|pds-training-attachments)/[a-zA-Z0-9_.-]+$~D', $path), 404);
        [$folder] = explode('/', $path);
        $record = match ($folder) {
            'leave-attachments' => LeaveRequest::withTrashed()->where('attachment_path', $path)->firstOrFail(),
            'cto-attachments' => CtoRequest::withTrashed()->where('attachment_path', $path)->firstOrFail(),
            'travel-order-attachments' => TravelOrder::withTrashed()->where('attachment_path', $path)->firstOrFail(),
            'travel-accomplishment-reports' => TravelOrder::withTrashed()->where('tar_attachment_path', $path)->firstOrFail(),
            'pds-training-attachments' => PdsTraining::where('attachment_path', $path)->first() ?? $this->trainingSnapshot($path),
        };
        $role = strtolower($request->user()->role);
        $employee = $request->user()->employee;
        $owner = (int) $record->employee_id === (int) $employee?->id;
        $hr = in_array($role, ['admin', 'hrstaff', 'regionaldirector'], true);
        $chief = $role === 'chief' && $folder !== 'pds-training-attachments' && filled($employee?->division)
            && strcasecmp(trim($employee->division), trim((string) $record->employee?->division)) === 0;
        $recordsOfficer = $role === 'recordofficer' && $record instanceof TravelOrder;
        abort_unless($owner || $hr || $chief || $recordsOfficer, 403);
        $disk = Storage::disk('documents');
        abort_unless($disk->exists($path), 404);
        $filename = $record->attachment_original_name ?: ($record->tar_attachment_original_name ?? 'Supporting document.pdf');
        return $disk->response($path, $filename, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'], 'inline');
    }

    private function trainingSnapshot(string $path)
    {
        foreach (\App\Models\PdsSnapshot::withTrashed()->cursor() as $snapshot) {
            foreach ([$snapshot->data, $snapshot->draft_data] as $data) {
                if (collect($data['relations']['pdsTrainings'] ?? [])->contains('attachment_path', $path)) return $snapshot;
            }
        }
        abort(404);
    }
}
