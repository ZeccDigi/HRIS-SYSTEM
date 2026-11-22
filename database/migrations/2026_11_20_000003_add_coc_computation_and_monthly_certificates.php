<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coc_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->date('earning_month');
            $table->date('issued_on');
            $table->date('valid_until');
            $table->timestamps();
            $table->unique(['employee_id', 'earning_month']);
        });
        Schema::table('cto_requests', function (Blueprint $table) {
            $table->decimal('rendered_hours', 10, 2)->nullable();
            $table->decimal('coc_rate', 4, 2)->nullable();
            $table->string('overtime_classification')->nullable();
            $table->foreignId('coc_certificate_id')->nullable()->constrained('coc_certificates')->restrictOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('cto_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coc_certificate_id');
            $table->dropColumn(['rendered_hours', 'coc_rate', 'overtime_classification']);
        });
        Schema::dropIfExists('coc_certificates');
    }
};
