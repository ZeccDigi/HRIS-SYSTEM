<?php
use App\Models\Employee;
use App\Models\User;

it('shows all four HR dashboard cards without records and retains pending details', function () {
    $user = User::factory()->create(['must_change_password' => false, 'is_approved' => true]);
    $employee = Employee::factory()->for($user)->create(['account_role' => 'hrstaff']);
    $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $cards = $response->viewData('latestApprovals');
    expect($cards->pluck('label')->all())->toBe(['Leave', 'CTO', 'PDS', 'SALN']);
    expect($cards->where('status', 'empty'))->toHaveCount(4);
    $response->assertSee('No pending records');
    foreach ($cards as $card) expect($card['url'])->not->toBe('#');
    $employee->pdsSectionReviews()->create(['section_name' => 'Personal Information', 'status' => 'pending']);
    $response = $this->get(route('dashboard'))->assertOk();
    $cards = $response->viewData('latestApprovals');
    expect($cards)->toHaveCount(4);
    expect($cards->firstWhere('label', 'PDS')['status'])->toBe('pending');
    expect($cards->firstWhere('label', 'PDS')['title'])->toBe('1 section updated');
    expect($cards->where('status', 'empty'))->toHaveCount(3);
});

it('also shows empty cards for Admin without adding approval cards to the employee dashboard', function () {
    $user = User::factory()->create(['must_change_password' => false, 'is_approved' => true]);
    $employee = Employee::factory()->for($user)->create(['account_role' => 'admin']);
    $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();
    expect($response->viewData('latestApprovals'))->toHaveCount(4);
    $employee->update(['account_role' => 'employee']);
    $this->actingAs($user->fresh());
    $response = $this->get(route('dashboard'))->assertOk();
    expect($response->viewData('latestApprovals'))->toBeEmpty();
});
