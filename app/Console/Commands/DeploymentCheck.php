<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
class DeploymentCheck extends Command {
 protected $signature = 'deployment:check';
 protected $description = 'Check production configuration without showing credentials or modifying data';
 public function handle(): int {
  $checks = [
   'Production environment' => app()->environment('production'),
   'Debug disabled' => !config('app.debug'),
   'HTTPS application URL' => str_starts_with((string)config('app.url'),'https://') && !str_contains((string)config('app.url'),'localhost'),
   'Secure session cookies' => (bool)config('session.secure'),
   'Application key configured' => filled(config('app.key')),
   'Database mail delivery configured' => !in_array(config('mail.default'),['log','array'],true),
   'No public employee signatures' => count(Storage::disk('public')->allFiles('employee-signatures')) === 0,
   'Private signature storage writable' => is_writable(storage_path('app/private')),
   'PDF browser available' => collect([config('services.pdf.browser_path'),'/usr/bin/chromium','/usr/bin/chromium-browser','/usr/bin/google-chrome', 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe','C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe'])->contains(fn($p)=>is_string($p)&&is_file($p)&&is_executable($p)),
  ];
  foreach ($checks as $label=>$passed) $this->line(($passed?'PASS: ':'FAIL: ').$label);
  $this->comment('Also verify the server scheduler, queue worker, private database access, and an off-server backup restoration. These cannot be proven from configuration alone.');
  return in_array(false,$checks,true)?self::FAILURE:self::SUCCESS;
 }
}
