<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medication_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('prescriber_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['active', 'on-hold', 'cancelled', 'completed', 'entered-in-error', 'stopped', 'draft', 'unknown'])->default('active');
            $table->enum('intent', ['proposal', 'plan', 'order', 'original-order', 'reflex-order', 'filler-order', 'instance-order', 'option'])->default('order');
            $table->string('category')->nullable();
            $table->string('medication_code')->nullable();
            $table->text('medication_display')->nullable();
            $table->text('dosage_instruction')->nullable();
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->string('route')->nullable();
            $table->integer('duration')->nullable()->comment('Duration in days');
            $table->text('note')->nullable();
            $table->date('authored_on')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index('medication_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_requests');
    }
};
