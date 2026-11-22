<?php

use App\Models\Employee;
use App\Models\User;
use App\Models\CtoRequest;

it('shows approved CTO totals and filters employee history with the selected main year', function () {
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::factory()->for($user)->create(['account_role' => 'employee']);
    $make = function ($type, $hours, $year, $status = 'approved', $source = null) use ($employee) {
        return CtoRequest::create(['employee_id' => $employee->id, 'type' => $type, 'hours' => $hours, 'date_start' => "$year-10-01", 'date_end' => "$year-10-01", 'purpose' => '', 'status' => $status, 'credit_source' => $source, 'cto_balance_after' => 10, 'credits_posted_at' => "$year-10-02 08:00:00"]);
    };
    $past = $make('earn', 3, 2025);
    $current = $make('earn', 6, 2026, 'approved', 'hr_direct');
    $used = $make('use', 2, 2026);
    $make('earn', 100, 2026, 'pending');
    $response = $this->actingAs($user)->get(route('my-cto.index', ['year' => 2025, 'tab' => 'credits']))->assertOk();
    expect($response->viewData('earnedCtoHours'))->toBe(9.0)->and($response->viewData('usedCtoHours'))->toBe(2.0);
    $response->assertSee('Approved Earned')->assertSee('Approved Used')->assertSee('historyYear: 2025', false)->assertDontSee('Quick Guide');
    $this->getJson(route('my-cto.credit-history', ['year' => 2025]))->assertOk()->assertJsonCount(1, 'rows')->assertJsonPath('rows.0.id', $past->id);
    $this->getJson(route('my-cto.credit-history', ['year' => 2026]))->assertOk()->assertJsonCount(2, 'rows');
});
