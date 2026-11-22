<?php
use App\Models\Employee;
use App\Models\User;
use App\Models\Announcement;

it('opens announcement management with no announcements and keeps the current year available', function () {
    $user = User::factory()->create(['must_change_password' => false, 'is_approved' => true]);
    Employee::factory()->for($user)->create(['account_role' => 'hrstaff']);
    $response = $this->actingAs($user)->get(route('announcements.index'))->assertOk();
    expect($response->viewData('years')->all())->toBe([now()->year]);
    expect($response->viewData('announcements')->total())->toBe(0);
    $announcement = Announcement::create(['title' => 'Test announcement', 'content' => 'Test content', 'author_id' => $user->id, 'is_published' => true]);
    $this->get(route('announcements.index'))->assertOk()->assertSee('Test announcement');
    $announcement->delete();
    $this->get(route('announcements.index'))->assertOk();
});
