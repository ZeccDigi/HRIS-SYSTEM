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
            'password' => 'New-Password9!',
            'password_confirmation' => 'New-Password9!',
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
            'password' => 'New-Password9!',
            'password_confirmation' => 'New-Password9!',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertTrue(Hash::check('New-Password9!', $user->refresh()->password));
});

test('password changes reject Low strength before sending an OTP', function (string $password) {
    Mail::fake();
    $user = User::factory()->create();
    $this->actingAs($user)->put('/password', ['password' => $password, 'password_confirmation' => $password])
        ->assertSessionHasErrorsIn('updatePassword', ['password']);
    Mail::assertNothingSent();
    expect($user->fresh()->otp)->toBeNull();
})->with(['lowercase only' => 'lowercase', 'uppercase only' => 'PASSWORD', 'numbers only' => '12345678', 'symbols only' => '________', 'too short' => 'Aa1!']);

test('underscore is accepted as a password special character', function () {
    Mail::fake();
    $user = User::factory()->create();
    Employee::factory()->for($user)->create(['notification_email' => 'recovery@example.test']);
    $this->actingAs($user)->put('/password', ['password' => 'New_Password9', 'password_confirmation' => 'New_Password9'])
        ->assertSessionHasNoErrors()->assertSessionHas('status', 'password-otp-sent');
    expect($user->fresh()->otp)->toHaveLength(6);
});

test('Medium strength passwords are accepted for password changes', function (string $password) {
    Mail::fake();
    $user = User::factory()->create();
    Employee::factory()->for($user)->create(['notification_email' => 'recovery@example.test']);
    $this->actingAs($user)->put('/password', ['password' => $password, 'password_confirmation' => $password])
        ->assertSessionHasNoErrors()->assertSessionHas('status', 'password-otp-sent');
})->with(['letters and numbers' => 'password9', 'mixed case letters' => 'Password', 'letters and underscore' => 'password_', 'uppercase and numbers' => 'PASSWORD9']);

test('password OTP resend waits thirty seconds on the server', function () {
    Mail::shouldReceive('raw')->twice()->andReturnNull();
    $user = User::factory()->create();
    Employee::factory()->for($user)->create(['notification_email' => 'recovery@example.test']);
    $this->actingAs($user)->putJson('/password', ['password' => 'Password9_', 'password_confirmation' => 'Password9_'])
        ->assertOk()->assertJsonPath('retry_after', 30);
    $otp = $user->fresh()->otp;
    $this->postJson('/password/otp')->assertStatus(429)->assertJsonPath('retry_after', 30);
    expect($user->fresh()->otp)->toBe($otp);
    $this->travel(31)->seconds();
    $this->postJson('/password/otp')->assertOk()->assertJsonPath('retry_after', 30);
});

test('failed OTP delivery reports an error and preserves the previous code', function () {
    Mail::shouldReceive('raw')->once()->andThrow(new RuntimeException('SMTP unavailable'));
    $user = User::factory()->create(['otp' => '123456', 'otp_expires_at' => now()->addMinutes(5)]);
    Employee::factory()->for($user)->create(['notification_email' => 'recovery@example.test']);
    $this->actingAs($user)->putJson('/password', ['password' => 'Password9_', 'password_confirmation' => 'Password9_'])
        ->assertStatus(503)->assertJsonPath('retry_after', 30)->assertJsonValidationErrors('otp');
    expect($user->fresh()->otp)->toBe('123456');
    $this->postJson('/password/otp')->assertStatus(429);
});
