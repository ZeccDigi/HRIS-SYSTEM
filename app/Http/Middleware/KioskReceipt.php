<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KioskReceipt
{
    public function handle(Request $request, Closure $next)
    {
        $id = $request->header('Idempotency-Key');
        if (! is_string($id) || ! Str::isUuid($id)) {
            return response()->json(['success' => false, 'message' => 'A scan request identifier is required.'], 422);
        }

        return DB::transaction(function () use ($request, $next, $id) {
            $device = DB::table('kiosk_devices')->where('id', $request->attributes->get('kiosk_device_id'))->lockForUpdate()->first();
            if (! $device || $device->revoked_at) {
                return response()->json(['success' => false, 'message' => 'The kiosk key is revoked.'], 401);
            }
            $hash = hash('sha256', $request->path().'|'.json_encode($request->all()));
            $receipt = DB::table('kiosk_receipts')->where('device_id', $device->id)->where('request_id', $id)->first();
            if ($receipt) {
                if (! hash_equals($receipt->payload_hash, $hash)) {
                    return response()->json(['success' => false, 'message' => 'Request identifier already used for another scan.'], 409);
                }

                return response()->json(json_decode($receipt->response, true), $receipt->status);
            }
            $response = $next($request);
            if ($response->getStatusCode() < 500 && $response instanceof JsonResponse) {
                DB::table('kiosk_receipts')->insert(['device_id' => $device->id, 'request_id' => $id, 'payload_hash' => $hash, 'status' => $response->getStatusCode(), 'response' => $response->getContent(), 'created_at' => now(), 'updated_at' => now()]);
            }

            return $response;
        });
    }
}
