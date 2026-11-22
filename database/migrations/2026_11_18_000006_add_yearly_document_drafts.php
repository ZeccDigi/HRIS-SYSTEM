<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', fn (Blueprint $table) => $table->unsignedSmallInteger('pds_record_year')->nullable());
        DB::table('employees')->update(['pds_record_year' => (int) now('Asia/Manila')->year]);
        Schema::table('pds_snapshots', function (Blueprint $table) {
            $table->json('draft_data')->nullable();
            $table->json('reviews')->nullable();
        });
        Schema::table('salns', fn (Blueprint $table) => $table->json('approved_data')->nullable());
    }

    public function down(): void
    {
        Schema::table('employees', fn (Blueprint $table) => $table->dropColumn('pds_record_year'));
        Schema::table('pds_snapshots', fn (Blueprint $table) => $table->dropColumn(['draft_data', 'reviews']));
        Schema::table('salns', fn (Blueprint $table) => $table->dropColumn('approved_data'));
    }
};
