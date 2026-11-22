<?php

use App\Models\CtoRequest;
use App\Models\Employee;
use App\Models\User;
use App\Services\CocGrantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Notification;

beforeEach(function () { $this->travelTo(\Carbon\Carbon::parse('2026-11-20 09:00:00', 'Asia/Manila')); });

function cocActor(string $role): array
{
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::create(['firstname' => ucfirst($role), 'lastname' => 'COC', 'division' => 'Test Division', 'account_role' => $role, 'user_id' => $user->id]);
    return [$user->fresh(), $employee];
}

test('HR direct addition posts credits and ledger immediately without an application approval', function () {
    Notification::fake();
    [$hr] = cocActor('hrstaff');
    [$owner, $employee] = cocActor('employee');
    [$chief] = cocActor('chief');
    [$director] = cocActor('regionaldirector');
    $opening = (float) $employee->cto_balance;
    $this->actingAs($hr)->post(route('employees.cto-credits.store', $employee), [
        'hours' => 4, 'overtime_date' => now()->subDay()->toDateString(),
    ])->assertRedirect()->assertSessionHas('cto_credit_success');
    $grant = CtoRequest::where('employee_id', $employee->id)->firstOrFail();
    expect($grant->status)->toBe('approved')->and($grant->recorded_by)->toBe($hr->id)
        ->and($grant->chief_status)->toBe('not_applicable')->and($grant->rd_status)->toBe('not_applicable')
        ->and((float) $employee->fresh()->cto_balance)->toBe($opening + 4)
        ->and((float) $grant->grant_remaining)->toBe(4.0);
    expect(app(\App\Services\CocCertificateService::class)->data($grant)['hours'])->toBe(4.0);
    Notification::assertSentTo($owner, \App\Notifications\CtoRequestNotification::class);
    Notification::assertNotSentTo($chief, \App\Notifications\CtoRequestNotification::class);
    Notification::assertNotSentTo($director, \App\Notifications\CtoRequestNotification::class);
    $this->actingAs($hr)->get(route('hr.cto.index', ['tab' => 'all']))
        ->assertOk()->assertViewHas('allCtoRequests', fn ($rows) => !$rows->contains('id', $grant->id));
    $this->actingAs($owner)->get(route('my-cto.index'))
        ->assertOk()->assertViewHas('ctoRequests', fn ($rows) => !$rows->contains('id', $grant->id));
    $this->actingAs($director)->put(route('cto.update-status', $grant), ['status' => 'approved'])->assertForbidden();
    expect((float) $employee->fresh()->cto_balance)->toBe($opening + 4);
});

function cocTestRequest(Employee $employee, array $attributes = []): CtoRequest
{
    return CtoRequest::create(array_merge([
        'employee_id' => $employee->id, 'type' => 'earn', 'hours' => 4,
        'date_start' => '2026-11-01', 'date_end' => '2026-11-01',
        'purpose' => 'Verified overtime', 'status' => 'approved',
    ], $attributes));
}

test('approved earning creates a dated individual grant', function () {
    $employee = Employee::create(['firstname' => 'Grant', 'lastname' => 'Employee', 'cto_balance' => 4]);
    $grant = cocTestRequest($employee);
    DB::transaction(function () use ($grant, $employee) {
        app(CocGrantService::class)->post($grant, $employee);
        $grant->save();
    });
    expect((float) $grant->fresh()->grant_remaining)->toBe(4.0)
        ->and($grant->fresh()->issued_on->toDateString())->toBe('2026-11-30')
        ->and($grant->fresh()->valid_until->toDateString())->toBe('2027-11-30');
});

test('use CTO allocates across grants and preserves the certificate ledger', function () {
    $employee = Employee::create(['firstname' => 'Usage', 'lastname' => 'Employee', 'cto_balance' => 7]);
    $employee->forceFill(['cto_balance' => 7])->save();
    $first = cocTestRequest($employee, ['grant_remaining' => 4, 'issued_on' => '2026-11-02']);
    $second = cocTestRequest($employee, ['hours' => 3, 'grant_remaining' => 3, 'issued_on' => '2026-11-03']);
    $use = cocTestRequest($employee, ['type' => 'use', 'hours' => 5, 'date_start' => '2026-11-20']);
    DB::transaction(fn () => app(CocGrantService::class)->post($use, $employee));
    expect((float) $first->fresh()->grant_remaining)->toBe(0.0)
        ->and((float) $second->fresh()->grant_remaining)->toBe(2.0);
    $this->assertDatabaseHas('coc_usages', ['grant_id' => $first->id, 'use_request_id' => $use->id, 'hours' => 4]);
    $this->assertDatabaseHas('coc_usages', ['grant_id' => $second->id, 'use_request_id' => $use->id, 'hours' => 1]);
});

test('expired grants cannot cover a later CTO application', function () {
    $employee = Employee::create(['firstname' => 'Expired', 'lastname' => 'Employee', 'cto_balance' => 4]);
    $employee->forceFill(['cto_balance' => 4])->save();
    $grant = cocTestRequest($employee, ['grant_remaining' => 4, 'issued_on' => '2026-11-02', 'valid_until' => '2026-11-10']);
    $use = cocTestRequest($employee, ['type' => 'use', 'hours' => 2, 'date_start' => '2026-11-20']);
    expect(fn () => DB::transaction(fn () => app(CocGrantService::class)->post($use, $employee)))
        ->toThrow(ValidationException::class);
    expect((float) $grant->fresh()->grant_remaining)->toBe(4.0);
    $this->assertDatabaseCount('coc_usages', 0);
});
