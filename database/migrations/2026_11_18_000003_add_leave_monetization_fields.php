<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
            $table->boolean('is_monetization')->default(false)->index();
            $table->string('monetization_basis')->nullable();
            $table->decimal('monetization_days', 9, 3)->nullable();
            $table->decimal('monetization_salary', 12, 2)->nullable();
            $table->decimal('monetization_amount', 14, 2)->nullable();
            $table->decimal('monetization_factor', 10, 7)->nullable();
            $table->text('monetization_justification')->nullable();
            $table->decimal('monetization_credit_before', 9, 3)->nullable();
            $table->decimal('monetization_credit_after', 9, 3)->nullable();
            $table->timestamp('monetization_verified_at')->nullable();
            $table->timestamp('monetization_posted_at')->nullable();
        });
        DB::table('leave_requests')->whereRaw('LOWER(TRIM(reason)) = ?', ['monetization of leave credits'])->update(['is_monetization' => true]);
    }

    public function down(): void
    {
        Schema::table('leave_requests', fn (Blueprint $table) => $table->dropColumn(['is_monetization', 'monetization_basis', 'monetization_days', 'monetization_salary', 'monetization_amount', 'monetization_factor', 'monetization_justification', 'monetization_credit_before', 'monetization_credit_after', 'monetization_verified_at', 'monetization_posted_at']));
        // Dates remain nullable so retained monetization rows remain valid on rollback.
    }
};
