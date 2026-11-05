<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Employee;
use App\Services\PdsSnapshotService;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pds_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->json('data');
            $table->timestamps();
            $table->unique(['employee_id', 'year']);
        });

        Employee::query()->withTrashed()->chunkById(100, function ($employees) {
            $snapshots = app(PdsSnapshotService::class);

            foreach ($employees as $employee) {
                $snapshots->capture($employee, (int) now()->year);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pds_snapshots');
    }
};
