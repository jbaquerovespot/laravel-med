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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->unique()->index()->comment('Cédula del paciente');
            $table->string('first_name')->comment('Primer nombre');
            $table->string('last_name')->comment('Apellido');
            $table->enum('gender', ['male', 'female', 'other', 'unknown'])->default('unknown');
            $table->date('birth_date')->comment('Fecha de nacimiento');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Colombia');
            $table->string('marital_status')->nullable()->comment('Estado civil');
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->foreignId('general_practitioner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            // Índices para búsquedas frecuentes
            $table->index('last_name');
            $table->index('birth_date');
            $table->index('gender');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
