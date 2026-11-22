<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('presentation_state',function(Blueprint $t){$t->string('key')->primary();$t->text('value')->nullable();});
  Schema::create('presentation_files',function(Blueprint $t){$t->id();$t->string('disk',32);$t->string('path',512);$t->string('checksum',64);$t->longText('payload');$t->timestamps();$t->unique(['disk','path']);});
 }
 public function down(): void {Schema::dropIfExists('presentation_files');Schema::dropIfExists('presentation_state');}
};
