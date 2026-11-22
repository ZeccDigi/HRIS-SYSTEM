<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('travel_orders', 'is_tar_required')) {
                $table->boolean('is_tar_required')->default(true)->after('tar_deadline');
            }
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            if (Schema::hasColumn('travel_orders', 'is_tar_required')) {
                $table->dropColumn('is_tar_required');
            }
        });
    }
};
