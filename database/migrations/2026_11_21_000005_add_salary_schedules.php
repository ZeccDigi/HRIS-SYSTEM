<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salary_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->string('salary_grade', 50);
            $table->string('salary_step', 50);
            $table->decimal('monthly_salary', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['year', 'salary_grade', 'salary_step']);
        });
        Schema::table('pds_work_experiences', function (Blueprint $table) {
            $table->string('salary_step', 50)->nullable();
            $table->unsignedSmallInteger('salary_schedule_year')->nullable();
        });
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('monetization_salary_grade', 50)->nullable();
            $table->string('monetization_salary_step', 50)->nullable();
            $table->unsignedSmallInteger('monetization_salary_year')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('leave_requests', fn (Blueprint $table) => $table->dropColumn(['monetization_salary_grade', 'monetization_salary_step', 'monetization_salary_year']));
        Schema::table('pds_work_experiences', fn (Blueprint $table) => $table->dropColumn(['salary_step', 'salary_schedule_year']));
        Schema::dropIfExists('salary_rates');
    }
};
