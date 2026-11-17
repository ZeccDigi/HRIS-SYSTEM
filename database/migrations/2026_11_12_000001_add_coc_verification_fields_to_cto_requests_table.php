<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cto_requests', function (Blueprint $table) {
            $table->string('overtime_authority_reference')->nullable()->after('purpose');
            $table->text('exception_justification')->nullable()->after('overtime_authority_reference');
            $table->decimal('verified_overtime_hours', 6, 2)->nullable()->after('hours');
            $table->timestamp('hr_verified_at')->nullable()->after('hrstaff_remarks');
        });
    }

    public function down(): void
    {
        Schema::table('cto_requests', function (Blueprint $table) {
            $table->dropColumn([
                'overtime_authority_reference',
                'exception_justification',
                'verified_overtime_hours',
                'hr_verified_at',
            ]);
        });
    }
};
