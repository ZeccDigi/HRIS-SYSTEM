<?php

use App\Http\Controllers\LocatorSlipController;

it('renders opaque QR images with a four module white border', function () {
    $controller = app(LocatorSlipController::class);
    $payload = 'LSQR:'.str_repeat('A', 32);
    $svg = (new ReflectionMethod($controller, 'buildQrSvg'))->invoke($controller, $payload);
    $png = (new ReflectionMethod($controller, 'buildQrPngData'))->invoke($controller, $payload);
    expect($svg)->toContain('viewBox=', 'fill="white"', 'x="4" y="4"');
    expect($png)->toBeString();
    $image = imagecreatefromstring($png);
    expect($image)->not->toBeFalse();
    expect(imagecolortransparent($image))->toBe(-1);
    foreach ([[0, 0], [31, 31], [imagesx($image)-1, imagesy($image)-1]] as [$x, $y]) {
        expect(imagecolorat($image, $x, $y) & 0xffffff)->toBe(0xffffff);
    }
    expect(imagecolorat($image, 32, 32) & 0xffffff)->toBe(0);
});

it('keeps a stable compact QR token for each approved slip', function () {
    $user = \App\Models\User::factory()->create();
    $employee = \App\Models\Employee::create(['user_id' => $user->id, 'firstname' => 'QR', 'lastname' => 'Test', 'position' => 'Staff', 'division' => 'Test', 'account_role' => 'employee']);
    $slip = \App\Models\LocatorSlip::create(['employee_id' => $employee->id, 'date_covered' => today(), 'destination' => 'Test', 'purpose' => 'Test', 'type' => 'Official Business', 'status' => 'approved']);
    $method = new ReflectionMethod(LocatorSlipController::class, 'appendQrMetadata');
    $first = $method->invoke(app(LocatorSlipController::class), $slip);
    $second = $method->invoke(app(LocatorSlipController::class), $slip->fresh());
    expect($first->qr_payload)->toMatch('/^LSQR:[A-F0-9]{32}$/')
        ->and($second->qr_payload)->toBe($first->qr_payload)
        ->and($second->qr_svg)->toContain('viewBox="0 0 33 33"');
});

it('returns recorded scan times only to authorized users', function () {
    $owner = \App\Models\User::factory()->create(['must_change_password' => false]);
    $employee = \App\Models\Employee::factory()->for($owner)->create(['account_role' => 'employee']);
    $slip = \App\Models\LocatorSlip::create(['employee_id' => $employee->id, 'date_covered' => today(), 'destination' => 'Test', 'purpose' => 'Test', 'type' => 'Pass Slip', 'status' => 'approved', 'time_from' => '13:30:00']);
    $this->actingAs($owner)->getJson(route('locator-slips.show', $slip))->assertOk()->assertJson(['out' => '01:30 PM', 'in' => null]);
    $slip->update(['time_to' => '15:30:00']);
    $this->getJson(route('locator-slips.show', $slip))->assertOk()->assertJson(['out' => '01:30 PM', 'in' => '03:30 PM']);
    $other = \App\Models\User::factory()->create(['must_change_password' => false]);
    \App\Models\Employee::factory()->for($other)->create(['account_role' => 'employee']);
    $this->actingAs($other)->getJson(route('locator-slips.show', $slip))->assertForbidden();
});
