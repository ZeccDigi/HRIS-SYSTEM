<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_audits', function (Blueprint $table) {
            $table->uuid('visit_token')->nullable()->unique()->after('ip_address');
            $table->timestamp('ended_at')->nullable()->after('visit_token');
            $table->unsignedInteger('duration_seconds')->nullable()->after('ended_at');
        });
    }

    public function down(): void
    {
        Schema::table('activity_audits', function (Blueprint $table) {
            $table->dropUnique(['visit_token']);
            $table->dropColumn(['visit_token', 'ended_at', 'duration_seconds']);
        });
    }
};
