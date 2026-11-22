<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmployeeEmailService
{
    public function sync(Employee $employee, string $email, bool $fromPds = false): void
    {
        $email = strtolower(trim($email));
        Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:255', Rule::unique('employees', 'notification_email')->ignore($employee->id)]])->validate();

        DB::transaction(function () use ($employee, $email, $fromPds) {
            $record = Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $record->update(['notification_email' => $email]);
            if ($fromPds) return;

            // Historical PDS records and approved print data retain their saved values.
            if ((int) ($record->pds_record_year ?? now()->year) !== (int) now()->year) {
                $snapshot = $record->pdsSnapshots()->where('year', now()->year)->first();
                if (!$snapshot && !app(PdsSnapshotService::class)->isArchivedForYear($record, (int) now()->year)) {
                    $snapshots = app(PdsSnapshotService::class);
                    $snapshot = $snapshots->capture($snapshots->employeeForYear($record, (int) now()->year), (int) now()->year);
                }
                if ($snapshot) {
                    $draft = $snapshot->draft_data ?? $snapshot->data;
                    $draft['relations']['pdsPersonal']['email_address'] = $email;
                    $reviews = collect($snapshot->reviews ?? []);
                    $review = $reviews->firstWhere('section_name', 'Personal Information') ?? ['section_name' => 'Personal Information'];
                    $review['status'] = 'pending';
                    $review['changed_fields'] = array_values(array_unique([...($review['changed_fields'] ?? []), 'email_address']));
                    $review['reviewed_by_id'] = null;
                    $review['remarks'] = null;
                    $reviews = $reviews->reject(fn ($item) => ($item['section_name'] ?? '') === 'Personal Information')->push($review);
                    $snapshot->update(['draft_data' => $draft, 'reviews' => $reviews->values()->all(), 'submitted_at' => now()]);
                }
                return;
            }
            if ($record->pdsPersonal?->email_address === $email) return;
            $snapshots = app(PdsSnapshotService::class);
            $snapshots->ensureApprovedBaseline($record);
            $record->pdsPersonal()->updateOrCreate(['employee_id' => $record->id], ['email_address' => $email]);
            $review = $record->pdsSectionReviews()->firstOrNew(['section_name' => 'Personal Information']);
            $fields = in_array($review->status, ['pending', 'rejected'], true) ? ($review->changed_fields ?? []) : [];
            $review->fill(['status' => 'pending', 'changed_fields' => array_values(array_unique([...$fields, 'email_address'])), 'reviewed_by_id' => null, 'remarks' => null])->save();
            $snapshots->syncLiveDraft($record, true);
        });
        $employee->refresh();
    }
}
