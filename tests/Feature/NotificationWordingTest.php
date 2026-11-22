<?php

use App\Support\NotificationTitle;
use App\Models\LeaveRequest;
use App\Models\CtoRequest;
use App\Notifications\CtoRequestNotification;
use App\Notifications\LeaveStatusUpdatedNotification;

test('application notification titles use sentence case and preserve acronyms', function () {
    foreach (['cto_fully_approved' => 'CTO request approved', 'cto_hr_approved' => 'CTO request ready for chief approval', 'cto_rejected' => 'CTO request rejected', 'TAR Approved' => 'TAR approved', 'Locator Slip Approved' => 'Locator slip approved', 'Leave Request Approved ✅' => 'Leave request approved'] as $raw => $expected) {
        expect(NotificationTitle::format($raw))->toBe($expected);
    }
    $cto = new CtoRequest(['status' => 'approved']);
    $cto->id = 1;
    expect((new CtoRequestNotification($cto, 'cto_fully_approved', 'Your CTO request has been fully approved.'))->toArray(new stdClass)['title'])->toBe('CTO request approved');
});

test('monetization notifications handle absent leave dates and distinguish intermediate approval', function () {
    $request = new LeaveRequest(['is_monetization' => true, 'status' => 'pending']);
    $request->id = 1;
    $pending = (new LeaveStatusUpdatedNotification($request, 'Verified/Recommended'))->toArray(new stdClass);
    expect($pending['title'])->toBe('Monetization request forwarded for approval')->and($pending['message'])->toContain('forwarded for further approval');
    $request->status = 'approved';
    expect((new LeaveStatusUpdatedNotification($request))->toArray(new stdClass)['title'])->toBe('Monetization request approved');
});
