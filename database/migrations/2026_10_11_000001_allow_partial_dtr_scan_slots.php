<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dtr_records', function (Blueprint $table) {
            if (!Schema::hasColumn('dtr_records', 'am_out')) {
                $table->time('am_out')->nullable()->after('time_in');
            }

            if (!Schema::hasColumn('dtr_records', 'pm_in')) {
                $table->time('pm_in')->nullable()->after('am_out');
            }
        });

        Schema::table('dtr_records', function (Blueprint $table) {
            $table->time('time_in')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('dtr_records', function (Blueprint $table) {
            $table->time('time_in')->nullable(false)->change();
        });

        Schema::table('dtr_records', function (Blueprint $table) {
            if (Schema::hasColumn('dtr_records', 'pm_in')) {
                $table->dropColumn('pm_in');
            }

            if (Schema::hasColumn('dtr_records', 'am_out')) {
                $table->dropColumn('am_out');
            }
        });
    }
};
