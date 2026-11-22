<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('travel_orders')
            ->where('tar_review_status', 'pending_hr')
            ->update([
                'tar_review_status' => 'pending_chief',
                'tar_hr_status' => 'not_applicable',
                'tar_reviewed_by_hr' => null,
                'tar_hr_remarks' => null,
            ]);
    }

    public function down(): void
    {
        DB::table('travel_orders')
            ->where('tar_review_status', 'pending_chief')
            ->where('tar_chief_status', 'pending')
            ->where('tar_rd_status', 'pending')
            ->update([
                'tar_review_status' => 'pending_hr',
                'tar_hr_status' => 'pending',
            ]);
    }
};
