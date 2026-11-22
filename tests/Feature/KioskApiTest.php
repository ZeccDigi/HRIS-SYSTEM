<?php

use App\Models\Employee;
use App\Models\LocatorSlip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function pairedKioskHeaders(): array
{
    $key = str_repeat('a', 64);
    DB::table('kiosk_devices')->insert(['name' => 'Test kiosk', 'token_hash' => hash('sha256', $key), 'created_at' => now(), 'updated_at' => now()]);

    return ['Authorization' => 'Bearer '.$key, 'Idempotency-Key' => (string) Str::uuid()];
}
function kioskEmployee(): Employee
{
    $user = User::factory()->create(['is_approved' => true, 'account_status' => 'active', 'must_change_password' => false]);

    return Employee::factory()->for($user)->create(['rfid_number' => '1234567890', 'account_role' => 'employee']);
}
it('requires a device key and rejects revoked keys', function () {
    $this->getJson('/api/kiosk/v1/status')->assertUnauthorized();
    $headers = pairedKioskHeaders();
    $this->getJson('/api/kiosk/v1/status', $headers)->assertOk()->assertJson(['success' => true, 'protocol' => 1]);
    DB::table('kiosk_devices')->update(['revoked_at' => now()]);
    $this->getJson('/api/kiosk/v1/status', $headers)->assertUnauthorized();
});
it('returns only the minimum active employee directory', function () {
    $headers = pairedKioskHeaders();
    $employee = kioskEmployee();
    $r = $this->getJson('/api/kiosk/v1/employees', $headers)->assertOk()->assertJsonPath('employees.0.id', $employee->id);
    expect($r->json('employees.0'))->not->toHaveKeys(['notification_email', 'monthly_salary', 'password', 'date_of_birth']);
    $employee->user->update(['account_status' => 'disabled']);
    $this->getJson('/api/kiosk/v1/employees', $headers)->assertJsonCount(0, 'employees');
});
it('uploads offline punches once and never overwrites a different existing punch', function () {
    $this->travelTo(Carbon::parse('2026-11-23 17:00:00', 'Asia/Manila'));
    $headers = pairedKioskHeaders();
    $employee = kioskEmployee();
    $data = ['server_id' => DB::table('kiosk_server_identity')->value('server_id'), 'records' => [['employee_id' => $employee->id, 'rfid_number' => $employee->rfid_number, 'date' => '2026-11-23', 'time_in' => '2026-11-23 08:00:00']]];
    $this->postJson('/api/kiosk/v1/attendance', $data, $headers)->assertOk();
    $this->postJson('/api/kiosk/v1/attendance', $data, $headers)->assertOk();
    $this->assertDatabaseCount('dtr_records', 1);
    $data['records'][0]['time_in'] = '2026-11-23 09:00:00';
    $this->postJson('/api/kiosk/v1/attendance', $data, $headers)->assertConflict();
    $headers['Idempotency-Key'] = (string) Str::uuid();
    $this->postJson('/api/kiosk/v1/attendance', $data, $headers)->assertUnprocessable();
    expect(DB::table('dtr_records')->value('time_in'))->toBe('08:00:00');
    $data['server_id'] = (string) Str::uuid();
    $headers['Idempotency-Key'] = (string) Str::uuid();
    $this->postJson('/api/kiosk/v1/attendance', $data, $headers)->assertConflict();
});
it('replays a locator OUT safely and records IN only for a new request after the guard', function () {
    $this->travelTo(Carbon::parse('2026-11-23 13:30:00', 'Asia/Manila'));
    $headers = pairedKioskHeaders();
    $employee = kioskEmployee();
    $slip = LocatorSlip::create(['employee_id' => $employee->id, 'date_covered' => '2026-11-23', 'destination' => 'Test office', 'purpose' => 'Test', 'type' => 'Official Business', 'status' => 'approved']);
    $slip->forceFill(['qr_token' => str_repeat('b', 32)])->save();
    $body = ['qr_code' => 'LSQR:'.str_repeat('b', 32)];
    $this->postJson('/api/kiosk/v1/locator-scan', $body, $headers)->assertOk()->assertJson(['type' => 'time_out']);
    $this->travel(1)->minutes();
    $this->postJson('/api/kiosk/v1/locator-scan', $body, $headers)->assertOk()->assertJson(['type' => 'time_out']);
    expect($slip->fresh()->time_to)->toBeNull();
    $headers['Idempotency-Key'] = (string) Str::uuid();
    $this->postJson('/api/kiosk/v1/locator-scan', $body, $headers)->assertOk()->assertJson(['type' => 'time_in']);
    expect($slip->fresh()->time_to)->not->toBeNull();
});
it('recognizes a wrong-date QR without writing and rejects weekend or pending slips', function () {
    $this->travelTo(Carbon::parse('2026-11-23 13:30:00', 'Asia/Manila'));
    $headers = pairedKioskHeaders();
    $employee = kioskEmployee();
    $slip = LocatorSlip::create(['employee_id' => $employee->id, 'date_covered' => '2026-11-24', 'destination' => 'Test', 'purpose' => 'Test', 'type' => 'Official Business', 'status' => 'approved']);
    $slip->forceFill(['qr_token' => str_repeat('c', 32)])->save();
    $body = ['qr_code' => 'LSQR:'.str_repeat('c', 32)];
    $this->postJson('/api/kiosk/v1/locator-scan', $body, $headers)->assertUnprocessable()->assertJson(['code' => 'schedule_mismatch']);
    expect($slip->fresh()->out_scanned_at)->toBeNull();
    $this->travelTo(Carbon::parse('2026-11-28 13:30:00', 'Asia/Manila'));
    $slip->update(['date_covered' => '2026-11-28']);
    $headers['Idempotency-Key'] = (string) Str::uuid();
    $this->postJson('/api/kiosk/v1/locator-scan', $body, $headers)->assertUnprocessable();
    $slip->update(['status' => 'pending']);
    $headers['Idempotency-Key'] = (string) Str::uuid();
    $this->postJson('/api/kiosk/v1/locator-scan', $body, $headers)->assertStatus(400);
});
it('allows Admin and HR to manage kiosks while denying other roles', function () {
    $employee = kioskEmployee();
    foreach (['employee', 'chief', 'regionaldirector'] as $role) {
        $employee->update(['account_role' => $role]);
        $this->actingAs($employee->user->fresh());
        $this->get(route('kiosk-devices.index'))->assertForbidden();
        $this->post(route('kiosk-devices.store'), ['name' => 'Not allowed'])->assertForbidden();
        $this->post(route('kiosk-devices.revoke', 1))->assertForbidden();
    }
    $this->assertDatabaseCount('kiosk_devices', 0);
    foreach (['admin', 'hrstaff'] as $role) {
        $employee->update(['account_role' => $role]);
        $this->actingAs($employee->user->fresh());
        $this->post(route('kiosk-devices.store'), ['name' => $role.' kiosk'])->assertRedirect()->assertSessionHas('kiosk_key');
        $device = DB::table('kiosk_devices')->latest('id')->first();
        expect($device->token_hash)->toBe(hash('sha256', session('kiosk_key')));
        $this->get(route('kiosk-devices.index'))->assertOk()->assertSee(route('kiosk-devices.index'), false);
        $this->post(route('kiosk-devices.revoke', $device->id))->assertRedirect();
        expect(DB::table('kiosk_devices')->where('id', $device->id)->value('revoked_at'))->not->toBeNull();
    }
});
