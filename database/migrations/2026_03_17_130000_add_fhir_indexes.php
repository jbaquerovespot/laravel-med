<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Performance indexes for FHIR searches
        Schema::table('patients', function (Blueprint $table) {
            $table->index('document_number', 'patients_document_number_idx');
            $table->index(['last_name', 'first_name'], 'patients_name_idx');
            $table->index('birth_date', 'patients_birth_date_idx');
        });

        Schema::table('encounters', function (Blueprint $table) {
            $table->index('patient_id', 'encounters_patient_id_idx');
            $table->index('status', 'encounters_status_idx');
            $table->index('start_datetime', 'encounters_start_datetime_idx');
        });

        Schema::table('observations', function (Blueprint $table) {
            $table->index('patient_id', 'observations_patient_id_idx');
            $table->index('effective_datetime', 'observations_effective_datetime_idx');
            $table->index('category', 'observations_category_idx');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('patients_document_number_idx');
            $table->dropIndex('patients_name_idx');
            $table->dropIndex('patients_birth_date_idx');
        });

        Schema::table('encounters', function (Blueprint $table) {
            $table->dropIndex('encounters_patient_id_idx');
            $table->dropIndex('encounters_status_idx');
            $table->dropIndex('encounters_start_datetime_idx');
        });

        Schema::table('observations', function (Blueprint $table) {
            $table->dropIndex('observations_patient_id_idx');
            $table->dropIndex('observations_effective_datetime_idx');
            $table->dropIndex('observations_category_idx');
        });
    }
};
