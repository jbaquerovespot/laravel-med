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
        Schema::table('service_requests', function (Blueprint $table) {
            $table->jsonb('fhir_resource')->nullable()->after('updated_at')
                ->comment('FHIR R4 ServiceRequest resource in JSON format');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX service_requests_fhir_resource_gin_idx ON service_requests USING GIN (fhir_resource)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('DROP INDEX IF EXISTS service_requests_fhir_resource_gin_idx');
            }
            $table->dropColumn('fhir_resource');
        });
    }
};
