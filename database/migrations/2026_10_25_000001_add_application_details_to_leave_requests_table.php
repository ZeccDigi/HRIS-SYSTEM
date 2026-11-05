<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('leave_detail_type')->nullable()->after('reason');
            $table->text('leave_detail')->nullable()->after('leave_detail_type');
            $table->boolean('commutation_requested')->nullable()->after('is_paid');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['leave_detail_type', 'leave_detail', 'commutation_requested']);
        });
    }
};
