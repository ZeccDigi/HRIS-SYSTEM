<?php

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

test('password update sends an OTP to the recovery email', function () {
    Mail::fake();

    $user = User::factory()->create();
    Employee::factory()->for($user)->create(['notification_email' => 'recovery@example.test']);

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile')
        ->assertSessionHas('status', 'password-otp-sent');

    expect($user->fresh()->otp)->toHaveLength(6);
});

test('password can be updated after the submitted OTP is confirmed', function () {
    $user = User::factory()->create();
    $user->update([
        'otp' => '123456',
        'otp_expires_at' => now()->addMinutes(10),
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password/confirm-otp', [
            'otp' => '123456',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
});
