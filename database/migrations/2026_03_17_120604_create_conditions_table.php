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
        Schema::create('conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('clinical_status')->default('active');
            $table->string('verification_status')->default('confirmed');
            $table->string('category')->nullable();
            $table->string('severity')->nullable();
            $table->string('code')->nullable();
            $table->text('display')->nullable();
            $table->text('note')->nullable();
            $table->date('onset_date')->nullable();
            $table->date('abatement_date')->nullable();
            $table->string('recorded_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'clinical_status']);
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conditions');
    }
};
