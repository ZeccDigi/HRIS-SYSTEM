<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salns', function (Blueprint $table) {
            if (! Schema::hasColumn('salns', 'review_status')) {
                $table->string('review_status')->default('pending')->after('net_worth');
            }

            if (! Schema::hasColumn('salns', 'review_remarks')) {
                $table->text('review_remarks')->nullable()->after('review_status');
            }

            if (! Schema::hasColumn('salns', 'reviewed_by_id')) {
                $table->foreignId('reviewed_by_id')->nullable()->after('review_remarks')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('salns', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_id');
            }

            if (! Schema::hasColumn('salns', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('reviewed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('salns', function (Blueprint $table) {
            if (Schema::hasColumn('salns', 'reviewed_by_id')) {
                $table->dropConstrainedForeignId('reviewed_by_id');
            }

            foreach (['submitted_at', 'reviewed_at', 'review_remarks', 'review_status'] as $column) {
                if (Schema::hasColumn('salns', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
