<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_core_time_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('applies_from');
            $table->date('core_time_start_date')->nullable();
            $table->date('core_time_end_date')->nullable();
            $table->json('core_time_schedule')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['employee_id', 'applies_from']);
        });
        DB::table('employees')->orderBy('id')->chunkById(200, function ($employees) {
            foreach ($employees as $employee) {
                DB::table('employee_core_time_schedules')->insert([
                    'employee_id' => $employee->id, 'applies_from' => '1900-01-01',
                    'core_time_start_date' => $employee->core_time_start_date,
                    'core_time_end_date' => $employee->core_time_end_date,
                    'core_time_schedule' => $employee->core_time_schedule,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_core_time_schedules');
    }
};
