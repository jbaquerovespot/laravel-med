<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega columna JSONB para almacenar recurso FHIR completo del Patient.
     * Incluye índice GIN para búsquedas eficientes en PostgreSQL.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Columna JSONB para almacenar recurso FHIR completo
            $table->jsonb('fhir_resource')->nullable()->after('updated_at')
                ->comment('FHIR R4 Patient resource in JSON format');
        });

        // Crear índice GIN para búsquedas en JSONB (solo PostgreSQL)
        // Este índice permite búsquedas eficientes con operadores @>, ?, etc.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX patients_fhir_resource_gin_idx ON patients USING GIN (fhir_resource)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Eliminar índice GIN si existe
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('DROP INDEX IF EXISTS patients_fhir_resource_gin_idx');
            }

            // Eliminar columna
            $table->dropColumn('fhir_resource');
        });
    }
};
