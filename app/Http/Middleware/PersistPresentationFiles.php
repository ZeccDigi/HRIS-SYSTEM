<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PresentationFiles;
class PersistPresentationFiles {
 public function handle(Request $request,Closure $next){
  if(!config('presentation.enabled') || $request->isMethodSafe())return $next($request);
  return DB::transaction(function()use($request,$next){
   DB::table('presentation_state')->where('key','files')->lockForUpdate()->first();
   $response=$next($request);
   app(PresentationFiles::class)->persist();
   return $response;
  },3);
 }
}
