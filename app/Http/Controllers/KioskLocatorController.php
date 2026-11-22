<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class KioskLocatorController extends Controller
{
    /**
     * Show Locator Slip Kiosk Scanning Page
     */
    public function index()
    {
        return view('locator_slip.index');
    }

    /**
     * Process scanned QR code from employee phone camera scanner
     */
    public function scan(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string|max:256',
        ]);

        $rawPayload = trim($request->input('qr_code'));
        if (empty($rawPayload)) {
            return response()->json([
                'success' => false,
                'message' => 'Empty QR code payload.',
            ], 400);
        }

        try {
            if (! preg_match('/^LSQR:([A-F0-9]{32})$/i', $rawPayload, $tokenMatch)) {
                return response()->json(['success' => false, 'message' => 'This QR code is not valid. Open the current QR from the approved locator slip.'], 422);
            }
            $qrToken = strtolower($tokenMatch[1]);
            $connection = config('database.default');

            return DB::connection($connection)->transaction(function () use ($connection, $qrToken, $rawPayload) {
                $locatorSlip = null;
                if (Schema::connection($connection)->hasTable('locator_slips')) {
                    $query = DB::connection($connection)->table('locator_slips');
                    if (Schema::connection($connection)->hasColumn('locator_slips', 'deleted_at')) {
                        $query->whereNull('deleted_at');
                    }

                    if ($qrToken) {
                        if (! Schema::connection($connection)->hasColumn('locator_slips', 'qr_token')) {
                            return response()->json(['success' => false, 'message' => 'The locator QR update is not available in the connected database.'], 503);
                        }
                        $locatorSlip = $query->where('qr_token', $qrToken)->lockForUpdate()->first();
                    }
                }

                // Reject unknown QR codes in production scanning.
                if (! $locatorSlip) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Locator Slip QR code was not found in the HRIS records.',
                    ], 404);
                }

                // Step 3: Verify Employee & Details
                $employee = DB::connection($connection)->table('employees')->where('id', $locatorSlip->employee_id)->first();
                $empName = $employee ? trim(($employee->firstname ?? '').' '.($employee->middlename ?? '').' '.($employee->lastname ?? '')) : 'Employee #'.$locatorSlip->employee_id;
                $division = $employee->division ?? 'DMW RO XI';

                if (! $employee || $employee->deleted_at || ! DB::table('users')->where('id', $employee->user_id)->where('account_status', '!=', 'disabled')->where('is_approved', true)->exists()) {
                    return response()->json(['success' => false, 'message' => 'Employee account is inactive or archived.'], 422);
                }

                // Check approval status
                $isApproved = in_array(strtolower($locatorSlip->status ?? ''), ['approved', 'approved by chief'], true);

                if (! $isApproved) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only an approved Locator Slip can be scanned.',
                    ], 400);
                }

                $scannedAt = now('Asia/Manila');
                if ($scannedAt->isWeekend()) {
                    return response()->json(['success' => false, 'message' => 'Locator slips cannot be used on weekends. No time was recorded.'], 422);
                }

                if (substr((string) $locatorSlip->date_covered, 0, 10) !== $scannedAt->toDateString()) {
                    return response()->json([
                        'success' => false,
                        'code' => 'schedule_mismatch',
                        'message' => 'QR code recognized. This locator slip is scheduled for '.substr((string) $locatorSlip->date_covered, 0, 10).'; today is '.$scannedAt->toDateString().'. No OUT or IN time was recorded.',
                    ], 422);
                }

                // Step 4: Record Time In / Time Out timestamp
                $scanType = '';
                $scanMsg = '';
                $updateField = '';

                if (empty($locatorSlip->time_from) || $locatorSlip->time_from === '00:00:00') {
                    $updateField = 'time_from';
                    $scanType = 'time_out';
                    $scanMsg = 'Time Out (Leaving Office) recorded for '.$empName;
                } elseif (empty($locatorSlip->time_to) || $locatorSlip->time_to === '00:00:00') {
                    if ($locatorSlip->out_scanned_at && Carbon::parse($locatorSlip->out_scanned_at, 'Asia/Manila')->diffInSeconds($scannedAt, false) < 10) {
                        return response()->json(['success' => false, 'message' => 'OUT was just recorded. Remove the QR and scan again when returning.'], 409);
                    }
                    $updateField = 'time_to';
                    $scanType = 'time_in';
                    $scanMsg = 'Time In (Return to Office) recorded for '.$empName;
                } else {
                    return response()->json([
                        'success' => true,
                        'type' => 'completed',
                        'message' => 'Locator Slip time logs are already completed for '.$empName,
                        'locator_slip' => [
                            'id' => $locatorSlip->id,
                            'control_no' => 'LS-'.str_pad($locatorSlip->id, 4, '0', STR_PAD_LEFT),
                            'employee_name' => $empName,
                            'date_covered' => date('F d, Y', strtotime($locatorSlip->date_covered)),
                            'division' => $division,
                            'office' => 'DMW RO XI',
                            'destination' => $locatorSlip->destination ?? 'Official Business',
                            'purpose' => $locatorSlip->purpose ?? '-',
                            'type' => $locatorSlip->type ?? 'Official Business',
                            'chief_name' => $locatorSlip->approved_by_chief_name ?? 'Division Chief',
                            'time_from' => date('h:i A', strtotime($locatorSlip->time_from)),
                            'time_to' => date('h:i A', strtotime($locatorSlip->time_to)),
                            'qr_code_raw' => $rawPayload,
                        ],
                    ]);
                }

                // Update database record with timestamp
                $scanValues = [
                    $updateField => $scannedAt->format('H:i:s'),
                    'updated_at' => $scannedAt,
                ];
                if ($scanType === 'time_out') {
                    $scanValues['out_scanned_at'] = $scannedAt;
                }
                DB::connection($connection)->table('locator_slips')->where('id', $locatorSlip->id)->update($scanValues);

                // Re-fetch updated record
                $updatedSlip = DB::connection($connection)->table('locator_slips')->where('id', $locatorSlip->id)->first();

                return response()->json([
                    'success' => true,
                    'type' => $scanType,
                    'message' => $scanMsg,
                    'locator_slip' => [
                        'id' => $updatedSlip->id,
                        'control_no' => 'LS-'.str_pad($updatedSlip->id, 4, '0', STR_PAD_LEFT),
                        'employee_name' => $empName,
                        'date_covered' => date('F d, Y', strtotime($updatedSlip->date_covered)),
                        'division' => $division,
                        'office' => 'DMW RO XI',
                        'destination' => $updatedSlip->destination ?? 'Official Business',
                        'purpose' => $updatedSlip->purpose ?? '-',
                        'type' => $updatedSlip->type ?? 'Official Business',
                        'chief_name' => $updatedSlip->approved_by_chief_name ?? 'Division Chief',
                        'time_from' => $updatedSlip->time_from ? date('h:i A', strtotime($updatedSlip->time_from)) : null,
                        'time_to' => $updatedSlip->time_to ? date('h:i A', strtotime($updatedSlip->time_to)) : null,
                        'qr_code_raw' => $rawPayload,
                    ],
                ]);

            });
        } catch (Exception $e) {
            Log::error('Locator Slip Scan Error', ['exception' => $e::class]);

            return response()->json([
                'success' => false,
                'message' => 'The locator slip could not be recorded. Please try again or contact HR.',
            ], 500);
        }
    }
}
