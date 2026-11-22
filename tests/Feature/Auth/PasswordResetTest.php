<?php

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

test('password recovery screen can be rendered', function () {
    $this->get('/forgot-password')->assertOk();
});

test('a password reset OTP is issued to an account recovery email', function () {
    Mail::fake();

    $user = User::factory()->create();
    Employee::factory()->for($user)->create([
        'notification_email' => 'recovery@example.test',
    ]);

    $response = $this->post('/forgot-password', ['email' => $user->email]);

    $response->assertRedirect(route('password.otp.reset', ['email' => $user->email]));
    $user->refresh();

    expect($user->otp)->toHaveLength(6)
        ->and($user->otp_expires_at)->not->toBeNull();
});

test('password can be reset with a valid recovery OTP', function () {
    $user = User::factory()->create([
        'otp' => '123456',
        'otp_expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->post('/reset-password-otp', [
        'email' => $user->email,
        'otp' => '123456',
        'password' => 'New-Secure-Password9!',
        'password_confirmation' => 'New-Secure-Password9!',
    ]);

    $response->assertRedirect(route('login'));
    $user->refresh();

    expect(Hash::check('New-Secure-Password9!', $user->password))->toBeTrue()
        ->and($user->otp)->toBeNull()
        ->and($user->otp_expires_at)->toBeNull();
});

test('a missing recovery email is explained without issuing an OTP', function () {
    Mail::fake();

    $user = User::factory()->create();
    Employee::factory()->for($user)->create(['notification_email' => null]);

    $this->from('/forgot-password')
        ->post('/forgot-password', ['email' => $user->email])
        ->assertRedirect('/forgot-password')
        ->assertSessionHasErrors('email');

    expect($user->fresh()->otp)->toBeNull();
});
