<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration creates the patient_fingerprints table for biometric
     * authentication according to Colombian regulations (Ley 1581 de 2012).
     * Fingerprint templates are encrypted using AES-256.
     */
    public function up(): void
    {
        Schema::create('patient_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('finger_identifier', 20)->comment('e.g., right_index, left_thumb');
            $table->text('encrypted_template')->comment('AES-256 encrypted fingerprint template');
            $table->string('template_hash', 64)->comment('SHA-256 hash for verification');
            $table->unsignedTinyInteger('quality_score')->default(0)->comment('0-100 quality score from SDK');
            $table->string('device_serial', 100)->nullable()->comment('Biometric device serial number');
            $table->string('device_model', 100)->nullable()->comment('Biometric device model');
            $table->timestamp('consent_date')->nullable()->comment('Date patient consented to biometric enrollment');
            $table->text('consent_document')->nullable()->comment('Path to signed consent document');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('capture_attempts')->default(1);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['patient_id', 'finger_identifier'], 'patient_finger_unique');
            $table->index('template_hash');
            $table->index('quality_score');
            $table->index('is_active');
        });

        // Add biometric_enrolled flag to patients table
        Schema::table('patients', function (Blueprint $table) {
            $table->boolean('biometric_enrolled')->default(false)->after('active');
            $table->timestamp('biometric_enrolled_at')->nullable()->after('biometric_enrolled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_fingerprints');

        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['biometric_enrolled', 'biometric_enrolled_at']);
        });
    }
};
