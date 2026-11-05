<?php

use App\Models\Announcement;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\NewAnnouncementNotification;
use Illuminate\Support\Facades\Notification;

function announcementUser(string $role, string $division, string $firstName, string $position = 'Employee'): User
{
    $user = User::factory()->create(['must_change_password' => false]);
    Employee::create([
        'firstname' => $firstName,
        'lastname' => 'User',
        'division' => $division,
        'position' => $position,
        'account_role' => $role,
        'employment_status' => 'Regular',
        'user_id' => $user->id,
    ]);

    return $user->fresh();
}

test('chief announcements are office orders delivered only to their division', function () {
    Notification::fake();
    $division = 'Migrant Workers Processing Division';
    $chief = announcementUser('chief', $division, 'Chief', 'Division Chief');
    $sameDivisionEmployee = announcementUser('employee', $division, 'Same', 'Regional Aide');
    $otherDivisionEmployee = announcementUser('employee', 'Finance and Administrative Division', 'Other');

    $response = $this->actingAs($chief)->post(route('announcements.store'), [
        'title' => 'Processing Division Schedule',
        'content' => 'Please follow the updated office schedule.',
        'tags' => 'General',
    ]);

    $response->assertRedirect(route('announcements.index'));
    $announcement = Announcement::firstOrFail();

    expect($announcement->tags)->toBe('Office Orders')
        ->and($announcement->audience_division)->toBe($division)
        ->and($announcement->author_id)->toBe($chief->id);

    Notification::assertSentTo($chief, NewAnnouncementNotification::class);
    Notification::assertSentTo($sameDivisionEmployee, NewAnnouncementNotification::class);
    Notification::assertNotSentTo($otherDivisionEmployee, NewAnnouncementNotification::class);

    $this->actingAs($sameDivisionEmployee)
        ->get(route('announcements.view'))
        ->assertOk()
        ->assertSee('Processing Division Schedule')
        ->assertSee('MWProcD - Division Chief');

    $this->actingAs($otherDivisionEmployee)
        ->get(route('announcements.view'))
        ->assertOk()
        ->assertDontSee('Processing Division Schedule');

    $this->actingAs($otherDivisionEmployee)
        ->get(route('announcements.show', $announcement))
        ->assertForbidden();
});

test('chief cannot edit another authors announcement', function () {
    $chief = announcementUser('chief', 'Migrant Workers Processing Division', 'Chief');
    $hr = announcementUser('hrstaff', 'Welfare and Reintegration Services Division', 'HR');
    $announcement = Announcement::create([
        'title' => 'Agency Announcement',
        'content' => 'Global information',
        'author_id' => $hr->id,
        'is_published' => true,
        'published_at' => now(),
        'tags' => 'General',
    ]);

    $this->actingAs($chief)
        ->get(route('announcements.edit', $announcement))
        ->assertForbidden();
});
