<?php
namespace App\Services;
use Illuminate\Support\Facades\{Crypt,DB,Storage};
use RuntimeException;
class PresentationFiles {
 private const FOLDERS = [
  'documents'=>['leave-attachments','cto-attachments','travel-order-attachments','travel-accomplishment-reports','pds-training-attachments'],
  'signatures'=>['employee-signatures','signatures','cto-signatures'],
  'public_uploads'=>['profile_pictures'], 'public'=>['profile_pictures'],
 ];
 private function allowed(string $disk,string $path): bool {
  return isset(self::FOLDERS[$disk]) && preg_match('~^[a-zA-Z0-9_-]+/[a-zA-Z0-9_.-]+$~D',$path)
    && in_array(explode('/',$path)[0],self::FOLDERS[$disk],true);
 }
 public function persist(): void {
  foreach(self::FOLDERS as $disk=>$folders){
   $seen=[];
   foreach($folders as $folder) foreach(Storage::disk($disk)->allFiles($folder) as $path){
    if(basename($path)==='.gitignore')continue;
    if(!$this->allowed($disk,$path))throw new RuntimeException('Invalid presentation file path.');
    $bytes=Storage::disk($disk)->get($path);
    if(strlen($bytes)>20*1024*1024)throw new RuntimeException('Presentation file exceeds the 20 MB storage limit.');
    $seen[]=$path;$hash=hash('sha256',$bytes);
    $old=DB::table('presentation_files')->where(compact('disk','path'))->first();
    if($old && hash_equals($old->checksum,$hash))continue;
    DB::table('presentation_files')->updateOrInsert(compact('disk','path'),[
      'checksum'=>$hash,'payload'=>Crypt::encryptString(base64_encode($bytes)),
      'created_at'=>$old?->created_at ?? now(),'updated_at'=>now(),
    ]);
   }
   $stale=DB::table('presentation_files')->where('disk',$disk);
   if($seen)$stale->whereNotIn('path',$seen);
   $stale->delete();
  }
 }
 public function restore(): void {
  foreach(DB::table('presentation_files')->orderBy('id')->cursor() as $file){
   if(!$this->allowed($file->disk,$file->path))throw new RuntimeException('Invalid stored presentation file path.');
   $bytes=base64_decode(Crypt::decryptString($file->payload),true);
   if($bytes===false || !hash_equals($file->checksum,hash('sha256',$bytes)))throw new RuntimeException('Presentation file integrity check failed.');
   if(!Storage::disk($file->disk)->put($file->path,$bytes))throw new RuntimeException('Could not restore presentation file.');
  }
 }
}
