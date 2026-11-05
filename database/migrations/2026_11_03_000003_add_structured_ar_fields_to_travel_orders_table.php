<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->string('tar_activity')->nullable()->after('tar_remarks');
            $table->date('tar_activity_date')->nullable()->after('tar_activity');
            $table->string('tar_venue')->nullable()->after('tar_activity_date');
            $table->text('tar_participants_present')->nullable()->after('tar_venue');
            $table->text('tar_dmw_participants')->nullable()->after('tar_participants_present');
            $table->text('tar_objectives')->nullable()->after('tar_dmw_participants');
            $table->text('tar_highlights')->nullable()->after('tar_objectives');
            $table->text('tar_results')->nullable()->after('tar_highlights');
            $table->text('tar_recommendations')->nullable()->after('tar_results');
            $table->text('tar_commitments')->nullable()->after('tar_recommendations');
            $table->json('tar_attachments')->nullable()->after('tar_commitments');
            $table->string('tar_other_attachment')->nullable()->after('tar_attachments');
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropColumn([
                'tar_activity',
                'tar_activity_date',
                'tar_venue',
                'tar_participants_present',
                'tar_dmw_participants',
                'tar_objectives',
                'tar_highlights',
                'tar_results',
                'tar_recommendations',
                'tar_commitments',
                'tar_attachments',
                'tar_other_attachment',
            ]);
        });
    }
};
