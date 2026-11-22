<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB,Hash};
use App\Models\{User,Employee,DtrRecord};
use App\Services\PresentationFiles;
class InitializePresentation extends Command {
 protected $signature='presentation:initialize';
 protected $description='Initialize a separate sample-only presentation database and restore its uploaded files';
 public function handle(): int {
  if(!config('presentation.enabled')){$this->error('Presentation mode must be explicitly enabled.');return self::FAILURE;}
  $password=config('presentation.password');
  if(!is_string($password)||strlen($password)<12){$this->error('Set PRESENTATION_PASSWORD to at least 12 characters.');return self::FAILURE;}
  if(!DB::table('presentation_state')->where('key','initialized')->exists()){
   if(User::query()->exists()){$this->error('Refusing to initialize a database that already contains accounts.');return self::FAILURE;}
   DB::transaction(function()use($password){
    $this->callSilent('db:seed',['--class'=>\Database\Seeders\LeaveTypeSeeder::class,'--force'=>true]);
    $this->callSilent('db:seed',['--class'=>\Database\Seeders\SalarySchedule2026Seeder::class,'--force'=>true]);
    foreach(['admin','hrstaff','chief','regionaldirector','recordofficer','employee'] as $role){
     $user=User::create(['name'=>'Demo '.ucfirst($role),'email'=>'demo.'.$role.'@dmw.gov.ph','password'=>Hash::make($password),'is_approved'=>true,'account_status'=>'active','must_change_password'=>false]);
     $user->forceFill(['email_verified_at'=>now()])->save();
     $employee=Employee::create(['user_id'=>$user->id,'firstname'=>'Demo','lastname'=>ucfirst($role),'position'=>ucfirst($role),'division'=>'Finance and Administrative Division','account_role'=>$role,'employment_status'=>'Regular','service_start_date'=>now()->startOfYear()->toDateString()]);
     $employee->pdsPersonal()->create(['surname'=>ucfirst($role),'firstname'=>'Demo','date_of_birth'=>'1990-01-15']);
     $employee->ensureLeaveCredits(now()->year);
     if($role==='employee')for($date=now()->startOfMonth();$date->lt(today());$date->addDay()){
      if($date->isWeekend())continue;
      DtrRecord::create(['employee_id'=>$employee->id,'date'=>$date->toDateString(),'time_in'=>'08:00:00','am_out'=>'12:00:00','pm_in'=>'13:00:00','time_out'=>'17:00:00','status'=>'Present']);
     }
    }
    DB::table('presentation_state')->insert([['key'=>'initialized','value'=>'1'],['key'=>'files','value'=>'1']]);
   });
  }
  app(PresentationFiles::class)->restore();
  $this->info('Presentation sample accounts are ready. Existing demo records were preserved.');
  return self::SUCCESS;
 }
}
