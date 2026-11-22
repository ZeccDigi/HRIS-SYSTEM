<?php
use App\Models\{User, Employee, UtilityOption, SalaryRate, LeaveType};
use App\Support\UtilityOptionRegistry;

function utilitiesHr() {
 $user = User::factory()->create(['must_change_password' => false]);
 Employee::create(['user_id'=>$user->id,'firstname'=>'Utilities','lastname'=>'Reviewer','account_role'=>'hrstaff']);
 return $user;
}

test('utility archives remain archived after defaults and can be restored in their category', function () {
 $this->actingAs(utilitiesHr());
 UtilityOptionRegistry::ensureDefaults();
 $option = UtilityOption::where('group_key','civil_statuses')->firstOrFail();
 $this->delete(route('utilities.options.destroy',$option))->assertRedirect();
 UtilityOptionRegistry::ensureDefaults();
 expect(UtilityOption::find($option->id))->toBeNull();
 $this->get(route('archive.index',['tab'=>'utility-civil_statuses']))->assertOk()->assertSee($option->label);
 $this->patch(route('archive.restore',['category'=>'utility-blood_types','record'=>$option->id]))->assertNotFound();
 $this->patch(route('archive.restore',['category'=>'utility-civil_statuses','record'=>$option->id]))->assertRedirect();
 expect(UtilityOption::find($option->id))->not->toBeNull();
});

test('salary and leave type archives retain records and salary filters return partial results', function () {
 $this->actingAs(utilitiesHr());
 $rate = SalaryRate::create(['year'=>2026,'salary_grade'=>'SG 2','salary_step'=>'Step 1','monthly_salary'=>15522,'is_active'=>true]);
 $this->getJson(route('utilities.index',['tab'=>'salary-schedule','salary_search'=>'SG 2']))->assertOk()->assertJsonStructure(['html'])->assertSee('15,522.00');
 $this->delete(route('utilities.salary-rates.destroy',$rate))->assertRedirect();
 $this->get(route('archive.index',['tab'=>'utility-salary-rates']))->assertOk()->assertSee('15,522.00');
 $this->patch(route('archive.restore',['category'=>'utility-salary-rates','record'=>$rate->id]))->assertRedirect();
 $leave = LeaveType::create(['name'=>'Archive sample','is_active'=>true]);
 $this->delete(route('leave-types.destroy',$leave))->assertRedirect();
 $this->assertSoftDeleted('leave_types',['id'=>$leave->id]);
 $this->get(route('archive.index',['tab'=>'utility-leave-types']))->assertOk()->assertSee('Archive sample');
});

test('utilities remembers selected tab and audit paginator stays in activity', function () {
 $this->actingAs(utilitiesHr());
 $this->get(route('utilities.index',['tab'=>'civil_statuses']))->assertOk();
 $this->get(route('utilities.index'))->assertOk()->assertViewHas('tab','civil_statuses');
 $this->get(route('audit-logs.login'))->assertOk()->assertViewHas('activityAudits',fn($pages)=>$pages->path() === route('audit-logs.activity'));
});
