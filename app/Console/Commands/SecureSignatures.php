<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
class SecureSignatures extends Command {
 protected $signature = 'security:secure-signatures';
 protected $description = 'Move public signature files to private storage without changing saved document paths';
 public function handle(): int {
  $public = Storage::disk('public'); $private = Storage::disk('signatures');
  $paths = collect($public->allFiles('employee-signatures'));
  $paths = $paths->merge(\App\Models\Employee::withTrashed()->whereNotNull('e_signature_path')->pluck('e_signature_path'))
   ->merge(\App\Models\PdsGovernmentId::whereNotNull('signature_path')->pluck('signature_path'))
   ->merge(\App\Models\CtoRequest::withTrashed()->whereNotNull('applicant_signature_path')->pluck('applicant_signature_path'))->filter()->unique();
  $moved = 0;
  foreach ($paths as $path) {
   if (!preg_match('~^[a-zA-Z0-9_-]+/[a-zA-Z0-9_.-]+$~D', $path)) { $this->error('Invalid signature path. Migration stopped.'); return self::FAILURE; }
   if (!$public->exists($path)) continue;
   $bytes = $public->get($path);
   if ($private->exists($path) && !hash_equals(hash('sha256',$bytes),hash('sha256',$private->get($path)))) { $this->error('Signature conflict. Migration stopped without removing the original.'); return self::FAILURE; }
   $private->put($path,$bytes);
   if (!hash_equals(hash('sha256',$bytes), hash('sha256',$private->get($path)))) { $this->error('Private copy verification failed. Original retained.'); return self::FAILURE; }
   $public->delete($path); $moved++;
  }
  $this->info("Secured {$moved} signature files. Saved document paths were preserved."); return self::SUCCESS;
 }
}
