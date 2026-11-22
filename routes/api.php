<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('kiosk/v1')->middleware([\App\Http\Middleware\AuthenticateKiosk::class, 'throttle:240,1'])->group(function () {
 Route::get('status', [\App\Http\Controllers\KioskApiController::class,'status']);
 Route::get('employees', [\App\Http\Controllers\KioskApiController::class,'employees']);
 Route::post('attendance', [\App\Http\Controllers\KioskApiController::class,'attendance'])->middleware(\App\Http\Middleware\KioskReceipt::class);
 Route::post('locator-scan', [\App\Http\Controllers\KioskLocatorController::class,'scan'])->middleware(\App\Http\Middleware\KioskReceipt::class);
});
