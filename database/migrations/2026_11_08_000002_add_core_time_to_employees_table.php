<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('core_time_start_date')->nullable()->after('service_start_date');
            $table->date('core_time_end_date')->nullable()->after('core_time_start_date');
            $table->json('core_time_schedule')->nullable()->after('core_time_end_date');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'core_time_start_date',
                'core_time_end_date',
                'core_time_schedule',
            ]);
        });
    }
};
