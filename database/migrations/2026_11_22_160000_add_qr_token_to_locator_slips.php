<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('locator_slips', function (Blueprint $table) {
            $table->string('qr_token', 32)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('locator_slips', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};
