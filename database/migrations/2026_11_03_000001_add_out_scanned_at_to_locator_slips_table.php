<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locator_slips', function (Blueprint $table) {
            $table->dateTime('out_scanned_at')->nullable()->after('time_from');
        });
    }

    public function down(): void
    {
        Schema::table('locator_slips', function (Blueprint $table) {
            $table->dropColumn('out_scanned_at');
        });
    }
};
