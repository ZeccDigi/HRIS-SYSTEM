<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ProtectSupportingDocuments extends Command
{
    protected $signature = 'documents:protect';
    protected $description = 'Move supporting documents out of public storage after verifying private copies';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('documents');
        $count = 0;
        foreach (['leave-attachments', 'cto-attachments', 'travel-order-attachments', 'travel-accomplishment-reports', 'pds-training-attachments', 'announcement-attachments'] as $folder) {
            foreach ($public->allFiles($folder) as $path) {
                if (!$private->exists($path)) {
                    $stream = $public->readStream($path);
                    try { $private->put($path, $stream); } finally { if (is_resource($stream)) fclose($stream); }
                }
                if (hash_file('sha256', $public->path($path)) !== hash_file('sha256', $private->path($path))) {
                    $this->error('Copy verification failed; public original retained: '.$path);
                    return self::FAILURE;
                }
                if (!$public->delete($path)) { $this->error('Could not remove public copy: '.$path); return self::FAILURE; }
                $count++;
            }
        }
        $this->info("Protected {$count} supporting documents.");
        return self::SUCCESS;
    }
}
