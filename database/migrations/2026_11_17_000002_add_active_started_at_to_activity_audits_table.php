<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_audits', function (Blueprint $table) {
            $table->timestamp('active_started_at')->nullable()->after('visit_token');
        });
    }

    public function down(): void
    {
        Schema::table('activity_audits', function (Blueprint $table) {
            $table->dropColumn('active_started_at');
        });
    }
};
