<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('travel_orders')
            ->whereNotNull('tar_submitted_at')
            ->whereNull('tar_review_status')
            ->update([
                'tar_review_status' => 'pending_hr',
                'tar_hr_status' => 'pending',
                'tar_chief_status' => 'pending',
                'tar_rd_status' => 'pending',
            ]);

        Schema::table('travel_orders', function (Blueprint $table) {
            $table->unique('ta_number', 'travel_orders_ta_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropUnique('travel_orders_ta_number_unique');
        });
    }
};
