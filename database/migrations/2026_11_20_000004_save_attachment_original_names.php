<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['leave_requests', 'cto_requests'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->text('attachment_original_name')->nullable());
        }
    }
    public function down(): void
    {
        foreach (['leave_requests', 'cto_requests'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('attachment_original_name'));
        }
    }
};
