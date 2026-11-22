<?php
use App\Models\{User,Employee};
use App\Services\PresentationFiles;
use Illuminate\Support\Facades\{DB,Hash,Storage,Mail};
function presentationConfig(): void {
 config(['presentation.enabled'=>true,'presentation.password'=>'Only-A-Test-Password_19']);
 foreach(['documents','signatures','public','public_uploads'] as $disk)Storage::fake($disk);
}
test('presentation initialization requires opt in and refuses an existing employee database',function(){
 config(['presentation.enabled'=>false]);
 $this->artisan('presentation:initialize')->assertFailed();
 presentationConfig(); User::factory()->create();
 $this->artisan('presentation:initialize')->assertFailed();
 expect(User::count())->toBe(1)->and(DB::table('presentation_state')->count())->toBe(0);
});
test('presentation creates sample accounts and preserves changes on the next start',function(){
 presentationConfig();
 $this->artisan('presentation:initialize')->assertSuccessful();
 expect(User::count())->toBe(6)->and(Employee::count())->toBe(6);
 $hr=User::where('email','demo.hrstaff@dmw.gov.ph')->firstOrFail();
 expect(Hash::check(config('presentation.password'),$hr->password))->toBeTrue()->and($hr->role)->toBe('hrstaff');
 $this->actingAs($hr)->get(route('dashboard'))->assertOk()->assertSee('Presentation preview');
 $hr->employee->update(['position'=>'Updated demo position']);
 $this->artisan('presentation:initialize')->assertSuccessful();
 expect(User::count())->toBe(6)->and($hr->employee->fresh()->position)->toBe('Updated demo position');
});
test('demo private uploads persist encrypted across loss of the web filesystem',function(){
 presentationConfig(); $path='leave-attachments/example.pdf';$bytes='%PDF-demo-private-payload';
 Storage::disk('documents')->put($path,$bytes); app(PresentationFiles::class)->persist();
 expect(DB::table('presentation_files')->first()->payload)->not->toContain($bytes);
 Storage::disk('documents')->delete($path); app(PresentationFiles::class)->restore();
 expect(Storage::disk('documents')->get($path))->toBe($bytes);
 Storage::disk('documents')->delete($path); app(PresentationFiles::class)->persist();
 expect(DB::table('presentation_files')->count())->toBe(0);
});
test('restoring demo uploads rejects unsafe paths',function(){
 presentationConfig();
 DB::table('presentation_files')->insert(['disk'=>'documents','path'=>'../../outside.txt','checksum'=>str_repeat('a',64),'payload'=>'invalid','created_at'=>now(),'updated_at'=>now()]);
 expect(fn()=>app(PresentationFiles::class)->restore())->toThrow(RuntimeException::class,'Invalid stored presentation file path.');
});
test('disabled presentation mail fails visibly instead of claiming delivery',function(){
 config(['mail.default'=>'disabled']);
 expect(fn()=>Mail::raw('Demo',fn($m)=>$m->to('sample@example.test')->subject('Demo')))->toThrow(\Symfony\Component\Mailer\Exception\TransportException::class,'Email delivery is not configured');
});
