<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kiosk_server_identity', function (Blueprint $t) {
            $t->unsignedInteger('id')->primary();
            $t->uuid('server_id')->unique();
        });
        DB::table('kiosk_server_identity')->insert(['id' => 1, 'server_id' => (string) Str::uuid()]);
        Schema::create('kiosk_devices', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100);
            $t->string('token_hash', 64)->unique();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('revoked_at')->nullable();
            $t->timestamp('last_seen_at')->nullable();
            $t->timestamps();
        });
        Schema::create('kiosk_receipts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('device_id')->constrained('kiosk_devices')->cascadeOnDelete();
            $t->uuid('request_id');
            $t->string('payload_hash', 64);
            $t->unsignedSmallInteger('status');
            $t->json('response');
            $t->timestamps();
            $t->unique(['device_id', 'request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kiosk_receipts');
        Schema::dropIfExists('kiosk_devices');
        Schema::dropIfExists('kiosk_server_identity');
    }
};
