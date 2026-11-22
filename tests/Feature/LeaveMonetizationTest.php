<?php

use App\Http\Controllers\DtrController;
use App\Models\DtrRecord;
use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveCreditTransaction;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveCreditService;
use App\Services\LeaveMonetizationService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

function monetizationFixture(): array
{
    Notification::fake();
    Storage::fake('public');
    \Illuminate\Support\Facades\Storage::fake('documents');
    Carbon::setTestNow(Carbon::parse('2026-11-18 10:00:00', 'Asia/Manila'));
    $users = [];
    foreach (['employee', 'hrstaff', 'chief', 'regionaldirector'] as $role) {
        $user = User::factory()->create(['must_change_password' => false]);
        Employee::create(['user_id' => $user->id, 'firstname' => 'Test', 'lastname' => $role, 'account_role' => $role, 'employment_status' => 'Regular', 'division' => 'Migrant Workers Processing Division', 'service_start_date' => '2025-01-01']);
        $users[$role] = $user->fresh();
    }
    $vl = LeaveType::create(['name' => 'Vacation Leave', 'is_active' => true, 'days_per_year' => 15]);
    $sl = LeaveType::create(['name' => 'Sick Leave', 'is_active' => true, 'days_per_year' => 15]);
    $employee = $users['employee']->employee;
    $employee->forceFill(['pds_approved_data' => ['relations' => ['pdsWorkExperiences' => [[
        'company' => 'DMW', 'date_from' => '2025-01-01', 'is_gov_service' => 1, 'salary_grade' => 'SG 11',
        'salary_step' => 'Step 1', 'salary_schedule_year' => 2026, 'monthly_salary' => 30000,
    ]]]]])->save();
    app(LeaveCreditService::class)->ensureCredits($employee, 2026);
    foreach ([$vl, $sl] as $type) $employee->leaveCredits()->where('leave_type_id', $type->id)->update(['adjusted' => 30]);
    app(LeaveCreditService::class)->ensureCredits($employee, 2026);
    return [$users, $employee, $vl, $sl];
}

function monetizationPayload(LeaveType $type, array $overrides = []): array
{
    return [...['leave_type_id' => $type->id, 'monetization_requested' => 1, 'monetization_basis' => 'regular', 'monetization_days' => 10, 'monetization_salary' => 30000], ...$overrides];
}

test('monetization files without absence dates and computes a server-side gross amount', function () {
    [$users, $employee, $vl] = monetizationFixture();
    $before = $employee->leaveCredits()->where('leave_type_id', $vl->id)->value('balance');
    $this->actingAs($users['employee'])->post(route('leaves.store'), monetizationPayload($vl, ['monetization_amount' => 999999]))
        ->assertSessionHasNoErrors()->assertRedirect(route('leaves.index', ['tab' => 'monetization']));
    $leave = $employee->leaveRequests()->firstOrFail();
    expect($leave->is_monetization)->toBeTrue()->and($leave->start_date)->toBeNull()->and($leave->end_date)->toBeNull()
        ->and($leave->leave_detail_type)->toBeNull()->and($leave->commutation_requested)->toBeTrue()
        ->and((float) $leave->monetization_amount)->toBe(14457.81)
        ->and($employee->leaveCredits()->where('leave_type_id', $vl->id)->value('balance'))->toBe($before);
    $this->get(route('leaves.print', $leave))->assertOk()->assertSee('Monetization of Leave Credits')->assertDontSee('10</span><span>days with pay', false);
    $this->get(route('leaves.index'))->assertOk()->assertViewHas('leaves', fn ($rows) => $rows->isEmpty());
    $this->get(route('leaves.index', ['tab' => 'monetization']))->assertOk()->assertSee('My Monetization')->assertSee('14,457.81');
});

test('HR Chief RD chain deducts once and never changes attendance', function () {
    [$users, $employee, $vl] = monetizationFixture();
    $record = DtrRecord::create(['employee_id' => $employee->id, 'date' => '2026-11-18', 'time_in' => '08:30:00', 'status' => 'present']);
    $before = (float) $employee->leaveCredits()->where('leave_type_id', $vl->id)->value('balance');
    $this->actingAs($users['employee'])->post(route('leaves.store'), monetizationPayload($vl))->assertSessionHasNoErrors();
    $leave = $employee->leaveRequests()->firstOrFail();
    $url = route('leave-applications.update', $leave);
    $this->actingAs($users['regionaldirector'])->put($url, ['status' => 'approved'])->assertForbidden();
    $this->actingAs($users['hrstaff'])->put($url, ['status' => 'approved'])->assertSessionHasErrors('monetization_verified');
    $this->put($url, ['status' => 'approved', 'monetization_verified' => 1])->assertSessionHasNoErrors()->assertRedirect();
    $this->actingAs($users['employee'])->put(route('leaves.update', $leave), monetizationPayload($vl))->assertForbidden();
    $this->actingAs($users['chief'])->put($url, ['status' => 'approved'])->assertSessionHasNoErrors();
    $this->actingAs($users['regionaldirector'])->put($url, ['status' => 'approved'])->assertSessionHasNoErrors();
    app(LeaveCreditService::class)->ensureCredits($employee, 2026);
    expect((float) $employee->leaveCredits()->where('leave_type_id', $vl->id)->value('balance'))->toBe(round($before - 10, 3))
        ->and($leave->fresh()->monetization_posted_at)->not->toBeNull();
    $this->put($url, ['status' => 'approved'])->assertForbidden();
    expect(LeaveCreditTransaction::where('leave_request_id', $leave->id)->where('type', 'monetization')->count())->toBe(1)
        ->and($record->fresh()->time_in)->toBe('08:30:00')->and(DtrRecord::count())->toBe(1);
    $method = new ReflectionMethod(DtrController::class, 'approvedAttendanceEvents');
    expect($method->invoke(new DtrController, Carbon::parse('2026-11-01'), Carbon::parse('2026-11-30'), [$employee->id]))->toBeEmpty();
    $this->actingAs($users['hrstaff'])->get(route('leave-applications.all', ['tab' => 'monetization']))->assertOk()->assertSee('Manage Monetization')->assertSee('14,457.81');
});

test('regular requests enforce available credits retention and annual pending cap', function () {
    [$users, $employee, $vl] = monetizationFixture();
    $this->actingAs($users['employee'])->post(route('leaves.store'), monetizationPayload($vl, ['monetization_days' => 5]))->assertSessionHasErrors('monetization_days');
    $this->post(route('leaves.store'), monetizationPayload($vl))->assertSessionHasNoErrors();
    $this->post(route('leaves.store'), monetizationPayload($vl, ['monetization_days' => 21]))->assertSessionHasErrors('monetization_days');
    expect($employee->leaveRequests()->count())->toBe(1);
});

test('Sick Leave follows exceptional rules and requires a supporting letter', function () {
    [$users, $employee, $vl, $sl] = monetizationFixture();
    $this->actingAs($users['employee'])->post(route('leaves.store'), monetizationPayload($sl))->assertSessionHasErrors('monetization_basis');
    $vlCredit = $employee->leaveCredits()->where('leave_type_id', $vl->id)->firstOrFail();
    $vlCredit->adjusted = (float) $vlCredit->adjusted - (float) $vlCredit->balance;
    $vlCredit->save();
    $data = monetizationPayload($sl, ['monetization_basis' => 'exceptional', 'monetization_days' => 25]);
    $this->post(route('leaves.store'), $data)->assertSessionHasErrors('attachment');
    $this->post(route('leaves.store'), [...$data, 'attachment' => UploadedFile::fake()->create('request-letter.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
    $leave = $employee->leaveRequests()->firstOrFail();
    expect($leave->is_monetization)->toBeTrue()->and($leave->attachment_path)->not->toBeNull();
    $this->get(route('leaves.edit', $leave))->assertOk()->assertSee('Highest monthly salary received');
    foreach (['hrstaff', 'chief', 'regionaldirector'] as $role) {
        $this->actingAs($users[$role])->put(route('leave-applications.update', $leave), ['status' => 'approved', 'monetization_verified' => 1])->assertRedirect()->assertSessionHasNoErrors();
    }
    expect($leave->fresh()->status)->toBe('approved')->and($leave->fresh()->monetization_justification)->toBeNull();
});

test('pending owner can edit monetization and other employees cannot', function () {
    [$users, $employee, $vl] = monetizationFixture();
    $this->actingAs($users['employee'])->post(route('leaves.store'), monetizationPayload($vl))->assertSessionHasNoErrors();
    $leave = $employee->leaveRequests()->firstOrFail();
    $this->put(route('leaves.update', $leave), monetizationPayload($vl, ['monetization_days' => 11, 'monetization_salary' => 31000]))->assertSessionHasNoErrors();
    expect((float) $leave->fresh()->monetization_amount)->toBe((new LeaveMonetizationService)->amount(30000, 11));
    $this->actingAs($users['chief'])->put(route('leaves.update', $leave), monetizationPayload($vl))->assertForbidden();
});

test('final approval rechecks credits and rolls back when the available balance changed', function () {
    [$users, $employee, $vl] = monetizationFixture();
    $this->actingAs($users['employee'])->post(route('leaves.store'), monetizationPayload($vl))->assertSessionHasNoErrors();
    $leave = $employee->leaveRequests()->firstOrFail();
    $url = route('leave-applications.update', $leave);
    $this->actingAs($users['hrstaff'])->put($url, ['status' => 'approved', 'monetization_verified' => 1])->assertSessionHasNoErrors();
    $this->actingAs($users['chief'])->put($url, ['status' => 'approved'])->assertSessionHasNoErrors();
    $employee->leaveCredits()->where('leave_type_id', $vl->id)->update(['adjusted' => 0]);
    $this->actingAs($users['regionaldirector'])->put($url, ['status' => 'approved'])->assertSessionHasErrors('monetization_days');
    expect($leave->fresh()->status)->toBe('pending')->and($leave->fresh()->monetization_posted_at)->toBeNull()
        ->and(LeaveCreditTransaction::where('leave_request_id', $leave->id)->where('type', 'monetization')->count())->toBe(0);
});

test('rendered modal switches monetization state without breaking ordinary leave fields', function () {
    [$users, $employee, $vl, $sl] = monetizationFixture();
    $response = $this->actingAs($users['employee'])->get(route('leaves.create', ['modal' => 1]))->assertOk();
    $dom = new DOMDocument;
    @$dom->loadHTML($response->getContent());
    $xpath = new DOMXPath($dom);
    $script = '';
    foreach ($xpath->query('//script[not(@src)]') as $node) if (str_contains($node->textContent, 'function leaveDateForm(')) $script = $node->textContent;
    $form = $xpath->query('//form[contains(@x-data,"leaveDateForm")]')->item(0);
    $picker = null;
    foreach ($xpath->query('//div[@x-data]') as $node) if (str_contains($node->getAttribute('x-data'), 'selectedId:')) $picker = $node;
    expect($form)->not->toBeNull()->and($picker)->not->toBeNull();
    $process = new \Symfony\Component\Process\Process(['node', '-e', <<<'JS'
const vm = require('vm');
const fs = require('fs');
const input = JSON.parse(fs.readFileSync(0, 'utf8'));
const context = vm.createContext({});
vm.runInContext(input.script, context);
const parent = vm.runInContext(input.form, context);
context.setDatePolicy = parent.setDatePolicy.bind(parent);
const child = vm.runInContext('(' + input.picker + ')', context);
const state = new Proxy({}, {
  get(_, key) { return key in child ? Reflect.get(child, key, state) : Reflect.get(parent, key, state); },
  set(_, key, value) { if (Object.hasOwn(parent, key)) parent[key] = value; else child[key] = value; return true; }
});

state.pick(input.vl);
if (parent.monetization !== false) throw Error('Ordinary leave must remain the initial mode');
parent.monetization = true;
if (state.monetization !== true) throw Error('Picker must inherit the parent monetization state');
state.pick(input.sl);
if (parent.monetizationBasis !== 'exceptional') throw Error('Sick Leave must choose exceptional monetization');
console.log('Modal state verified');
JS]);
    $process->setInput(json_encode(['script' => $script, 'form' => $form->getAttribute('x-data'), 'picker' => $picker->getAttribute('x-data'), 'vl' => (string) $vl->id, 'sl' => (string) $sl->id], JSON_THROW_ON_ERROR));
    $process->mustRun();
    expect($process->getOutput())->toContain('Modal state verified');
});

test('repeated submission reuses the pending monetization instead of reserving credits twice', function () {
    [$users, $employee, $vl] = monetizationFixture();
    $this->actingAs($users['employee']);
    foreach ([1, 2] as $attempt) $this->post(route('leaves.store'), monetizationPayload($vl))->assertSessionHasNoErrors();
    expect($employee->leaveRequests()->where('is_monetization', true)->count())->toBe(1)
        ->and(LeaveCreditTransaction::where('type', 'monetization')->count())->toBe(0);
});

test('rejection releases the pending reservation without a credit deduction', function () {
    [$users, $employee, $vl] = monetizationFixture();
    $before = $employee->leaveCredits()->where('leave_type_id', $vl->id)->value('balance');
    $this->actingAs($users['employee'])->post(route('leaves.store'), monetizationPayload($vl))->assertSessionHasNoErrors();
    $leave = $employee->leaveRequests()->firstOrFail();
    $this->actingAs($users['hrstaff'])->put(route('leave-applications.update', $leave), ['status' => 'approved', 'monetization_verified' => 1])->assertSessionHasNoErrors();
    $this->actingAs($users['chief'])->put(route('leave-applications.update', $leave), ['status' => 'rejected', 'remarks' => 'Not approved for the current funding period.'])->assertSessionHasNoErrors();
    app(LeaveCreditService::class)->ensureCredits($employee, 2026);
    expect($leave->fresh()->status)->toBe('rejected')
        ->and($employee->leaveCredits()->where('leave_type_id', $vl->id)->value('balance'))->toBe($before)
        ->and(LeaveCreditTransaction::where('type', 'monetization')->count())->toBe(0);
});
