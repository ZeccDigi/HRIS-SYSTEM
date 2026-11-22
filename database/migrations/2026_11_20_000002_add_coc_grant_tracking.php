<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cto_requests', function (Blueprint $table) {
            $table->string('credit_source')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('issued_on')->nullable();
            $table->date('valid_until')->nullable();
            $table->decimal('grant_remaining', 10, 2)->nullable();
        });
        Schema::create('coc_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grant_id')->constrained('cto_requests')->restrictOnDelete();
            $table->foreignId('use_request_id')->constrained('cto_requests')->restrictOnDelete();
            $table->decimal('hours', 10, 2);
            $table->decimal('remaining_after', 10, 2);
            $table->timestamps();
            $table->unique(['grant_id', 'use_request_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('coc_usages');
        Schema::table('cto_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropColumn(['credit_source', 'issued_on', 'valid_until', 'grant_remaining']);
        });
    }
};
