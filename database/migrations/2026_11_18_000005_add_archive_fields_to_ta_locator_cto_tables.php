<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['travel_orders', 'locator_slips', 'cto_requests'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                if (! Schema::hasColumn($name, 'deleted_at')) {
                    $table->softDeletes();
                    $table->index('deleted_at');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['travel_orders', 'locator_slips', 'cto_requests'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                if (Schema::hasColumn($name, 'deleted_at')) {
                    $table->dropIndex(['deleted_at']);
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};
