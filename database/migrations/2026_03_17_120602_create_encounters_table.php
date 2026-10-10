<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->nullOnDelete();
            $table->enum('status', ['planned', 'in-progress', 'on-hold', 'discharged', 'completed', 'cancelled', 'discontinued', 'entered-in-error', 'unknown'])->default('planned');
            $table->datetime('start_datetime');
            $table->datetime('end_datetime')->nullable();
            $table->string('reason_code')->nullable()->comment('SNOMED-CT code for reason');
            $table->string('reason_text')->nullable()->comment('Reason description');
            $table->string('class_code')->default('AMB')->comment('ActCode: AMB=ambulatory, IMP=inpatient, etc.');
            $table->string('class_display')->default('ambulatory');
            $table->string('service_type_code')->nullable();
            $table->string('service_type_display')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('patient_id');
            $table->index('status');
            $table->index('start_datetime');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounters');
    }
};
