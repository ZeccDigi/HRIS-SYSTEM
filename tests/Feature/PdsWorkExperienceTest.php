<?php

use App\Models\Employee;
use App\Models\PdsWorkExperience;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

it('serializes database boolean government service values for the numeric dropdown', function () {
    foreach ([true => 1, false => 0] as $raw => $expected) {
        $work = new PdsWorkExperience;
        $work->setRawAttributes(['is_gov_service' => (bool) $raw]);
        expect($work->toArray()['is_gov_service'])->toBe($expected);
    }
});

it('keeps government service selections on employee and HR forms and publishes only after approval', function () {
    Notification::fake();
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::factory()->for($user)->create(['account_role' => 'employee']);
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($hr)->create(['account_role' => 'hrstaff']);

    foreach ([1, 0, 1] as $value) {
        $this->actingAs($user)->put(route('pds.update'), ['work_experience' => [[
            'position_title' => 'Test Officer', 'company' => 'Test Agency',
            'date_from' => '2020-01-01', 'is_gov_service' => (string) $value,
        ]]])->assertSessionHasNoErrors();
        expect($employee->pdsWorkExperiences()->firstOrFail()->toArray()['is_gov_service'])->toBe($value);
        $this->get(route('pds.edit'))->assertOk()->assertSee('&quot;is_gov_service&quot;:'.$value, false);
        $this->actingAs($hr)->get(route('employees.pds.show', $employee))->assertOk()
            ->assertSee('&quot;is_gov_service&quot;:'.$value, false);
        $this->post(route('pds-reviews.store', $employee), ['section_name' => 'Work Experience', 'status' => 'approved'])->assertRedirect();
        expect((int) data_get($employee->fresh()->pds_approved_data, 'relations.pdsWorkExperiences.0.is_gov_service'))->toBe($value);
    }
});
