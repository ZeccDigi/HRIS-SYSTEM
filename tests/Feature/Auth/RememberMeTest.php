<?php

use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

test('remember me issues a persistent cookie and restores a cleared login session', function () {
    $user = User::factory()->create(['email' => 'remember.test@dmw.gov.ph', 'must_change_password' => false, 'privacy_consent' => true, 'is_approved' => true, 'account_status' => 'active']);
    Employee::create(['user_id' => $user->id, 'firstname' => 'Remember', 'lastname' => 'Test', 'account_role' => 'employee']);
    $name = Auth::guard('web')->getRecallerName();
    $response = $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => 1]);
    $response->assertSessionHasNoErrors()->assertCookie($name);
    $cookie = $response->getCookie($name);
    expect($cookie->getExpiresTime())->toBeGreaterThan(time())->and($user->fresh()->remember_token)->not->toBeNull();
    $this->flushSession();
    Auth::forgetGuards();
    $this->withCookie($name, $cookie->getValue())->get('/dashboard')->assertOk();
    $this->assertAuthenticatedAs($user);
    expect(Auth::guard('web')->viaRemember())->toBeTrue();
    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});
