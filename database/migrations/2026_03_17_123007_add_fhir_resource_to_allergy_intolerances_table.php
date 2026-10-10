<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('allergy_intolerances', function (Blueprint $table) {
            $table->jsonb('fhir_resource')->nullable()->after('updated_at')
                ->comment('FHIR R4 AllergyIntolerance resource in JSON format');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX allergy_intolerances_fhir_resource_gin_idx ON allergy_intolerances USING GIN (fhir_resource)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('allergy_intolerances', function (Blueprint $table) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('DROP INDEX IF EXISTS allergy_intolerances_fhir_resource_gin_idx');
            }
            $table->dropColumn('fhir_resource');
        });
    }
};
