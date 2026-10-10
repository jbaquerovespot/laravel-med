<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['registered', 'preliminary', 'final', 'amended', 'revised', 'cancelled', 'entered-in-error', 'unknown'])->default('final');
            $table->string('category')->nullable()->comment('vital-signs, laboratory, imaging, etc.');
            $table->string('code')->comment('LOINC code');
            $table->string('display')->comment('Observation name');
            $table->datetime('effective_datetime');
            $table->enum('value_type', ['Quantity', 'String', 'CodeableConcept', 'Range', 'boolean'])->default('Quantity');
            $table->float('value_quantity')->nullable();
            $table->string('value_unit')->nullable();
            $table->text('value_string')->nullable();
            $table->string('interpretation')->nullable()->comment('N=normal, H=high, L=low, etc.');
            $table->float('reference_range_low')->nullable();
            $table->float('reference_range_high')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('patient_id');
            $table->index('encounter_id');
            $table->index('effective_datetime');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observations');
    }
};
