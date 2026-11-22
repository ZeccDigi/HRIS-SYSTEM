<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('salary_grade', 50)->nullable();
            $table->string('salary_step', 50)->nullable();
            $table->unsignedSmallInteger('salary_schedule_year')->nullable();
            $table->decimal('monthly_salary', 12, 2)->nullable();
        });
        Schema::table('pds_work_experiences', fn (Blueprint $table) => $table->boolean('hr_salary_assignment')->default(false));
    }
    public function down(): void
    {
        Schema::table('pds_work_experiences', fn (Blueprint $table) => $table->dropColumn('hr_salary_assignment'));
        Schema::table('employees', fn (Blueprint $table) => $table->dropColumn(['salary_grade', 'salary_step', 'salary_schedule_year', 'monthly_salary']));
    }
};
