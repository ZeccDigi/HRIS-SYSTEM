<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pds_section_reviews', function (Blueprint $table) {
            $table->json('changed_fields')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('pds_section_reviews', function (Blueprint $table) {
            $table->dropColumn('changed_fields');
        });
    }
};
