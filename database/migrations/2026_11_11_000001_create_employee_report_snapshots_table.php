<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_month');
            $table->json('data');
            $table->timestamps();

            $table->unique(['employee_id', 'snapshot_month']);
            $table->index('snapshot_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_report_snapshots');
    }
};
