<?php

use App\Models\ActivityAudit;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

test('page visit duration accumulates active segments without counting paused time', function () {
    $this->freezeSecond();
    $user = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($user)->create(['account_role' => 'employee']);

    $start = $this->actingAs($user)->postJson(route('activity-visits.start'), [
        'module' => 'PDS',
        'page' => 'Personal Data Sheet',
        'route' => 'pds.index',
    ])->assertOk();

    $token = $start->json('visit_token');
    $this->travel(10)->seconds();

    $this->postJson(route('activity-visits.end'), ['visit_token' => $token])->assertOk();
    expect(ActivityAudit::where('visit_token', $token)->value('duration_seconds'))->toBe(10);

    $this->travel(30)->seconds();
    $this->postJson(route('activity-visits.start'), [
        'module' => 'PDS',
        'page' => 'Personal Data Sheet',
        'route' => 'pds.index',
        'visit_token' => $token,
    ])->assertOk();

    $this->travel(7)->seconds();
    $this->postJson(route('activity-visits.end'), ['visit_token' => $token])->assertOk();

    expect(ActivityAudit::where('visit_token', $token)->value('duration_seconds'))->toBe(17)
        ->and(ActivityAudit::where('visit_token', $token)->count())->toBe(1);
});

test('retention pruning removes only expired page visits', function () {
    $expiredVisit = ActivityAudit::create([
        'user_name' => 'Old User',
        'user_role' => 'Employee',
        'action' => 'Page Visit',
        'module' => 'PDS',
        'description' => 'Visited Personal Data Sheet',
        'visit_token' => (string) Illuminate\Support\Str::uuid(),
    ]);
    $expiredVisit->forceFill(['created_at' => now()->subDays(366)])->save();

    $otherAudit = ActivityAudit::create([
        'user_name' => 'Old User',
        'user_role' => 'Employee',
        'action' => 'Updated PDS',
        'module' => 'PDS',
        'description' => 'Updated Personal Data Sheet',
    ]);
    $otherAudit->forceFill(['created_at' => now()->subDays(366)])->save();

    Artisan::call('activity:prune-visits', ['--days' => 365]);

    $this->assertSoftDeleted('activity_audits', ['id' => $expiredVisit->id]);
    expect(ActivityAudit::find($expiredVisit->id))->toBeNull();
    $this->assertDatabaseHas('activity_audits', ['id' => $otherAudit->id]);

    $user = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($user)->create(['account_role' => 'employee']);
    $url = route('archive.index', ['tab' => 'logs']);
    $this->actingAs($user)->get($url)->assertForbidden();

    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($hr)->create(['account_role' => 'hrstaff']);
    $this->actingAs($hr)->get($url)
        ->assertOk()
        ->assertSee('Visited Personal Data Sheet')
        ->assertDontSee('Updated Personal Data Sheet');
});
