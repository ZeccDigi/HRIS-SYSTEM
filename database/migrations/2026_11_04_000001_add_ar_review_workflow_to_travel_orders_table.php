<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->string('tar_review_status')->nullable()->after('tar_status');
            $table->string('tar_hr_status')->default('pending')->after('tar_review_status');
            $table->foreignId('tar_reviewed_by_hr')->nullable()->after('tar_hr_status')->constrained('employees')->nullOnDelete();
            $table->text('tar_hr_remarks')->nullable()->after('tar_reviewed_by_hr');
            $table->string('tar_chief_status')->default('pending')->after('tar_hr_remarks');
            $table->foreignId('tar_reviewed_by_chief')->nullable()->after('tar_chief_status')->constrained('employees')->nullOnDelete();
            $table->text('tar_chief_remarks')->nullable()->after('tar_reviewed_by_chief');
            $table->string('tar_rd_status')->default('pending')->after('tar_chief_remarks');
            $table->foreignId('tar_reviewed_by_rd')->nullable()->after('tar_rd_status')->constrained('employees')->nullOnDelete();
            $table->text('tar_rd_remarks')->nullable()->after('tar_reviewed_by_rd');
            $table->timestamp('tar_approved_at')->nullable()->after('tar_rd_remarks');
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tar_reviewed_by_hr');
            $table->dropConstrainedForeignId('tar_reviewed_by_chief');
            $table->dropConstrainedForeignId('tar_reviewed_by_rd');
            $table->dropColumn([
                'tar_review_status',
                'tar_hr_status',
                'tar_hr_remarks',
                'tar_chief_status',
                'tar_chief_remarks',
                'tar_rd_status',
                'tar_rd_remarks',
                'tar_approved_at',
            ]);
        });
    }
};
