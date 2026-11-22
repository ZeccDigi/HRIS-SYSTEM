<?php

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\CtoRequest;
use App\Services\CocEntitlementService;
use App\Services\CocGrantService;
use App\Services\CocCertificateService;
use Illuminate\Support\Facades\DB;

test('employee live quote and submitted earning use the same server calculation', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-23 09:00:00', 'Asia/Manila'));
    \Illuminate\Support\Facades\Notification::fake();
    $user = \App\Models\User::factory()->create(['must_change_password' => false]);
    $employee = Employee::create(['firstname' => 'Quote', 'lastname' => 'Employee', 'user_id' => $user->id, 'account_role' => 'employee']);
    $this->actingAs($user)->getJson(route('coc.quote', ['date' => '2026-11-21', 'hours' => 2]))
        ->assertOk()->assertJson(['rendered_hours' => 2, 'coc_rate' => 1.5, 'hours' => 3]);
    \Illuminate\Support\Facades\Storage::fake('public');
    \Illuminate\Support\Facades\Storage::fake('documents');
    $this->actingAs($user)->post(route('my-cto.store'), ['type' => 'earn', 'date_start' => '2026-11-21', 'hours' => 2, 'coc_rate' => 99, 'attachment' => \Illuminate\Http\UploadedFile::fake()->create('overtime.pdf', 10, 'application/pdf')])
        ->assertRedirect();
    $record = CtoRequest::where('employee_id', $employee->id)->firstOrFail();
    expect((float) $record->hours)->toBe(3.0)->and((float) $record->rendered_hours)->toBe(2.0)
        ->and((float) $record->coc_rate)->toBe(1.5)->and($record->status)->toBe('pending');
    $this->actingAs($user)->getJson(route('coc.quote', ['date' => '2026-11-21', 'hours' => 1]))->assertUnprocessable();
});

test('COC rate uses assigned workdays rather than weekend name and recognizes holidays', function () {
    $employee = Employee::create(['firstname' => 'Rate', 'lastname' => 'Test', 'core_time_schedule' => [
        'saturday' => ['am_start' => '08:00', 'am_end' => '12:00'],
    ]]);
    $service = app(CocEntitlementService::class);
    expect($service->calculate($employee, '2026-11-21', 2)['hours'])->toBe(2.0)
        ->and($service->calculate($employee, '2026-11-23', 2)['hours'])->toBe(3.0);
    Holiday::create(['name' => 'Test holiday', 'date' => '2026-11-21']);
    expect($service->calculate($employee, '2026-11-21', 2)['hours'])->toBe(3.0);
});

test('same month approved earnings share a certificate and aggregate one usage entry', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-12-01', 'Asia/Manila'));
    $employee = Employee::create(['firstname' => 'Monthly', 'lastname' => 'Test']);
    $employee->forceFill(['cto_balance' => 6])->save();
    $grants = collect();
    foreach (['2026-11-21', '2026-11-22'] as $date) {
        $grant = CtoRequest::create(['employee_id' => $employee->id, 'type' => 'earn', 'date_start' => $date, 'date_end' => $date, 'rendered_hours' => 2, 'hours' => 3, 'purpose' => '', 'status' => 'approved']);
        DB::transaction(function () use ($grant, $employee) { app(CocGrantService::class)->post($grant, $employee); $grant->save(); });
        $grants->push($grant);
    }
    expect($grants[0]->coc_certificate_id)->toBe($grants[1]->coc_certificate_id)
        ->and($grants[0]->valid_until->toDateString())->toBe('2027-12-01');
    $use = CtoRequest::create(['employee_id' => $employee->id, 'type' => 'use', 'date_start' => '2026-12-02', 'date_end' => '2026-12-02', 'hours' => 4, 'purpose' => '', 'status' => 'approved']);
    DB::transaction(fn () => app(CocGrantService::class)->post($use, $employee));
    $certificate = app(CocCertificateService::class)->data($grants[0]);
    expect($certificate['hours'])->toBe(6.0)->and($certificate['remaining'])->toBe(2.0)
        ->and($certificate['usages'])->toHaveCount(1)
        ->and($certificate['usages'][0]->hours)->toBe(4.0);
});
