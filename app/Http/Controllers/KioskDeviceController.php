<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KioskDeviceController extends Controller
{
    public function index()
    {
        return redirect()->route('utilities.index', ['tab' => 'kiosk-connections']);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100']);
        $key = bin2hex(random_bytes(32));
        DB::table('kiosk_devices')->insert(['name' => $data['name'], 'token_hash' => hash('sha256', $key), 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);

        return redirect()->route('utilities.index', ['tab' => 'kiosk-connections'])->with('kiosk_key', $key);
    }

    public function revoke(int $device)
    {
        DB::table('kiosk_devices')->where('id', $device)->update(['revoked_at' => now(), 'updated_at' => now()]);

        return redirect()->route('utilities.index', ['tab' => 'kiosk-connections'])->with('success', 'Kiosk access revoked.');
    }
}
