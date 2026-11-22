<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('leave_credit_transactions', 'credit_year')) {
            Schema::table('leave_credit_transactions', fn (Blueprint $table) => $table->unsignedSmallInteger('credit_year')->nullable());
        }
        DB::table('leave_credit_transactions')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $metadata = json_decode($row->metadata ?? '{}', true);
                $year = $metadata['year'] ?? \Carbon\Carbon::parse($row->effective_date)->year;
                DB::table('leave_credit_transactions')->where('id', $row->id)->update(['credit_year' => $year]);
            }
        });
        // Add the replacement first: MySQL also uses the leading request ID to support its foreign key.
        if (!Schema::hasIndex('leave_credit_transactions', 'lct_request_type_year_unique')) {
            Schema::table('leave_credit_transactions', fn (Blueprint $table) => $table->unique(['leave_request_id', 'type', 'credit_year'], 'lct_request_type_year_unique'));
        }
        if (Schema::hasIndex('leave_credit_transactions', 'lct_request_type_unique')) {
            Schema::table('leave_credit_transactions', fn (Blueprint $table) => $table->dropUnique('lct_request_type_unique'));
        }
    }
    public function down(): void
    {
        Schema::table('leave_credit_transactions', function (Blueprint $table) {
            $table->dropUnique('lct_request_type_year_unique');
            $table->dropColumn('credit_year');
        });
        // Year-spanning postings must remain intact; do not restore the old incompatible unique key.
    }
};
