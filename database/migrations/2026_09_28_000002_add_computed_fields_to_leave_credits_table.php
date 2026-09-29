<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_credits', function (Blueprint $table) {
            $table->decimal('balance', 8, 3)->default(0)->change();
            $table->decimal('earned', 8, 3)->default(0)->after('balance');
            $table->decimal('used', 8, 3)->default(0)->after('earned');
            $table->decimal('adjusted', 8, 3)->default(0)->after('used');
            $table->date('accrued_as_of')->nullable()->after('year');
        });
    }

    public function down(): void
    {
        Schema::table('leave_credits', function (Blueprint $table) {
            $table->dropColumn(['earned', 'used', 'adjusted', 'accrued_as_of']);
            $table->decimal('balance', 8, 2)->default(0)->change();
        });
    }
};
