<?php

namespace App\Services;

use App\Models\CtoRequest;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CocGrantService
{
    // Call within the balance owner's approval transaction.
    public function post(CtoRequest $request, Employee $employee): void
    {
        $request->credits_posted_at ??= now('Asia/Manila');
        if ($request->type === 'earn') {
            if ((float) ($request->rendered_hours ?? $request->hours) < 2) {
                throw ValidationException::withMessages(['hours' => 'At least 2 rendered hours are required to earn COC.']);
            }
            $request->credit_source = $request->credit_source ?: 'employee_request';
            $month = $request->date_start->copy()->startOfMonth();
            $certificate = DB::table('coc_certificates')->where('employee_id', $employee->id)
                ->whereDate('earning_month', $month->toDateString())->first();
            if (!$certificate) {
                $issue = max($month->copy()->endOfMonth()->toDateString(), now('Asia/Manila')->toDateString());
                $id = DB::table('coc_certificates')->insertGetId([
                    'employee_id' => $employee->id, 'earning_month' => $month->toDateString(),
                    'issued_on' => $issue, 'valid_until' => \Carbon\Carbon::parse($issue)->addYearNoOverflow()->toDateString(),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $certificate = DB::table('coc_certificates')->find($id);
            }
            $request->coc_certificate_id = $certificate->id;
            $request->issued_on = $certificate->issued_on;
            $request->valid_until = $certificate->valid_until;
            $request->grant_remaining = $request->hours;
            return;
        }

        $hours = (float) $request->hours;
        if ($hours > (float) $employee->cto_balance) {
            throw ValidationException::withMessages(['status' => 'The employee no longer has enough COC credits.']);
        }
        $grants = CtoRequest::where('employee_id', $employee->id)->where('type', 'earn')
            ->where('status', 'approved')->where('grant_remaining', '>', 0)
            ->orderBy('issued_on')->orderBy('id')->lockForUpdate()->get();
        $eligible = $grants->filter(fn ($grant) => !$grant->valid_until || $grant->valid_until->toDateString() >= $request->date_start->toDateString());
        // Earlier balances remain available without inventing historical certificates.
        $legacyBalance = max(0, (float) $employee->cto_balance - (float) $grants->sum('grant_remaining'));
        if ($hours > (float) $eligible->sum('grant_remaining') + $legacyBalance) {
            throw ValidationException::withMessages(['status' => 'Insufficient COC credits valid on the requested CTO date.']);
        }
        foreach ($eligible as $grant) {
            if ($hours <= 0) break;
            $used = min($hours, (float) $grant->grant_remaining);
            $remaining = round((float) $grant->grant_remaining - $used, 2);
            $grant->update(['grant_remaining' => $remaining]);
            DB::table('coc_usages')->insert([
                'grant_id' => $grant->id, 'use_request_id' => $request->id,
                'hours' => $used, 'remaining_after' => $remaining,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $hours = round($hours - $used, 2);
        }
    }
}
