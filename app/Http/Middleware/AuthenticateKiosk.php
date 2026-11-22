<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthenticateKiosk
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        if (! $token || strlen($token) > 200) {
            return response()->json(['success' => false, 'message' => 'A valid kiosk key is required.'], 401);
        }
        $device = DB::table('kiosk_devices')->where('token_hash', hash('sha256', $token))->whereNull('revoked_at')->first();
        if (! $device) {
            return response()->json(['success' => false, 'message' => 'The kiosk key is invalid or revoked.'], 401);
        }
        $request->attributes->set('kiosk_device_id', $device->id);
        DB::table('kiosk_devices')->where('id', $device->id)->update(['last_seen_at' => now()]);

        return $next($request)->header('Cache-Control', 'no-store');
    }
}
