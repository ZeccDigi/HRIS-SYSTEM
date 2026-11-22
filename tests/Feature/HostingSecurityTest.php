<?php
use App\Models\{Employee, User};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Storage, RateLimiter};

test('chief and director cannot replace or remove another employee signature', function (string $role) {
 Storage::fake('signatures');
 $user = User::factory()->create(['must_change_password'=>false]);
 Employee::create(['user_id'=>$user->id,'firstname'=>'Approver','lastname'=>'Test','account_role'=>$role]);
 $owner = Employee::create(['firstname'=>'Owner','lastname'=>'Test','account_role'=>'employee']);
 $this->actingAs($user)->post(route('employees.e-signature',$owner), ['e_signature'=>UploadedFile::fake()->image('signature.png')])->assertForbidden();
 $this->delete(route('employees.e-signature.destroy',$owner))->assertForbidden();
})->with(['chief','regionaldirector']);

test('owner uploads signature privately and it remains printable without a public link', function () {
 Storage::fake('signatures'); Storage::fake('public');
 $user = User::factory()->create(['must_change_password'=>false]);
 $owner = Employee::create(['user_id'=>$user->id,'firstname'=>'Owner','lastname'=>'Test','account_role'=>'employee']);
 $this->actingAs($user)->post(route('employees.e-signature',$owner), ['e_signature'=>UploadedFile::fake()->image('signature.png')])->assertRedirect();
 $owner->refresh();
 Storage::disk('signatures')->assertExists($owner->e_signature_path);
 Storage::disk('public')->assertMissing($owner->e_signature_path);
 expect($owner->effective_signature_url)->toStartWith('data:image/png;base64,');
});

test('OTP verification locks after five guesses even when the IP changes', function () {
 $user = User::factory()->create(['otp'=>'123456','otp_expires_at'=>now()->addMinutes(10)]);
 $this->actingAs($user);
 foreach (range(1,5) as $attempt) $this->putJson('/password/confirm-otp',['otp'=>'000000','password'=>'Password9_','password_confirmation'=>'Password9_'])->assertStatus(422);
 $this->withServerVariables(['REMOTE_ADDR'=>'192.0.2.8'])->putJson('/password/confirm-otp',['otp'=>'123456','password'=>'Password9_','password_confirmation'=>'Password9_'])->assertStatus(429);
 expect($user->fresh()->otp)->toBe('123456');
});
