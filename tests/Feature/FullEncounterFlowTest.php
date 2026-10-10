<?php

namespace Tests\Feature;

use App\Models\Condition;
use App\Models\Encounter;
use App\Models\MedicationRequest;
use App\Models\Observation;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FullEncounterFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'patients.view']);
        Permission::create(['name' => 'patients.create']);
        Permission::create(['name' => 'encounters.create']);
        Permission::create(['name' => 'rda.generate']);

        // Create doctor role
        $role = Role::create(['name' => 'doctor']);
        $role->givePermissionTo(['patients.view', 'patients.create', 'encounters.create', 'rda.generate']);

        $this->doctor = User::factory()->create();
        $this->doctor->assignRole('doctor');
    }

    #[Test]
    public function complete_encounter_workflow(): void
    {
        // 1. Login as doctor
        $this->actingAs($this->doctor);

        // 2. Create a new patient
        $patient = Patient::create([
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'gender' => 'male',
            'birth_date' => '1990-01-15',
            'active' => true,
        ]);

        $this->assertDatabaseHas('patients', [
            'document_number' => '12345678',
        ]);

        // 3. Create encounter
        $encounter = Encounter::create([
            'patient_id' => $patient->id,
            'status' => 'finished',
            'class' => 'AMB',
            'type' => 'consultation',
            'reason' => 'Dolor de garganta y fiebre',
            'note' => 'Paciente presenta faringitis aguda. Se prescribe tratamiento antibiótico.',
        ]);

        $this->assertDatabaseHas('encounters', [
            'patient_id' => $patient->id,
            'reason' => 'Dolor de garganta y fiebre',
        ]);

        // 4. Create vital signs observations
        $vitalSigns = [
            ['code' => '8480-6', 'value' => 120, 'unit' => 'mmHg', 'display' => 'Systolic BP'],
            ['code' => '8462-4', 'value' => 80, 'unit' => 'mmHg', 'display' => 'Diastolic BP'],
            ['code' => '8867-4', 'value' => 72, 'unit' => '/min', 'display' => 'Heart Rate'],
            ['code' => '8310-5', 'value' => 37.5, 'unit' => '°C', 'display' => 'Temperature'],
        ];

        foreach ($vitalSigns as $vital) {
            Observation::create([
                'patient_id' => $patient->id,
                'encounter_id' => $encounter->id,
                'code' => $vital['code'],
                'value' => $vital['value'],
                'unit' => $vital['unit'],
                'status' => 'final',
                'category' => 'vital-signs',
            ]);
        }

        $this->assertEquals(4, Observation::where('encounter_id', $encounter->id)->count());

        // 5. Create diagnosis
        $condition = Condition::create([
            'patient_id' => $patient->id,
            'encounter_id' => $encounter->id,
            'code' => 'J02',
            'note' => 'Faringitis aguda',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'category' => 'principal',
        ]);

        $this->assertDatabaseHas('conditions', [
            'patient_id' => $patient->id,
            'code' => 'J02',
        ]);

        // 6. Create medication prescription
        $medication = MedicationRequest::create([
            'patient_id' => $patient->id,
            'encounter_id' => $encounter->id,
            'medication' => 'Amoxicilina',
            'dosage' => '500mg',
            'frequency' => 'cada 8 horas',
            'duration' => '7 días',
            'instructions' => 'Tomar con alimentos',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('medication_requests', [
            'patient_id' => $patient->id,
            'medication' => 'Amoxicilina',
        ]);

        // 7. Verify FHIR resources generated
        $patient->refresh();
        $encounter->refresh();

        $this->assertNotNull($patient->fhir_resource);
        $this->assertEquals('Patient', $patient->fhir_resource['resourceType']);

        $this->assertNotNull($encounter->fhir_resource);
        $this->assertEquals('Encounter', $encounter->fhir_resource['resourceType']);

        // 8. Verify audit logs
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Patient::class,
            'subject_id' => $patient->id,
            'description' => 'patient.created',
        ]);
    }

    #[Test]
    public function encounter_with_multiple_diagnoses(): void
    {
        $this->actingAs($this->doctor);

        $patient = Patient::factory()->create();
        $encounter = Encounter::create([
            'patient_id' => $patient->id,
            'status' => 'finished',
            'class' => 'AMB',
            'reason' => 'Control de diabetes e hipertensión',
        ]);

        // Principal diagnosis
        Condition::create([
            'patient_id' => $patient->id,
            'encounter_id' => $encounter->id,
            'code' => 'E11',
            'note' => 'Diabetes mellitus tipo 2',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'category' => 'principal',
        ]);

        // Secondary diagnosis
        Condition::create([
            'patient_id' => $patient->id,
            'encounter_id' => $encounter->id,
            'code' => 'I10',
            'note' => 'Hipertensión esencial',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'category' => 'secondary',
        ]);

        $this->assertEquals(2, Condition::where('encounter_id', $encounter->id)->count());
    }

    #[Test]
    public function encounter_with_multiple_medications(): void
    {
        $this->actingAs($this->doctor);

        $patient = Patient::factory()->create();
        $encounter = Encounter::create([
            'patient_id' => $patient->id,
            'status' => 'finished',
            'class' => 'AMB',
            'reason' => 'Tratamiento múltiple',
        ]);

        // Multiple medications
        $medications = [
            ['medication' => 'Metformina', 'dosage' => '850mg', 'frequency' => 'cada 12 horas'],
            ['medication' => 'Enalapril', 'dosage' => '10mg', 'frequency' => 'una vez al día'],
            ['medication' => 'Atorvastatina', 'dosage' => '20mg', 'frequency' => 'una vez al día'],
        ];

        foreach ($medications as $med) {
            MedicationRequest::create([
                'patient_id' => $patient->id,
                'encounter_id' => $encounter->id,
                'status' => 'active',
                'duration' => '30 días',
                ...$med,
            ]);
        }

        $this->assertEquals(3, MedicationRequest::where('encounter_id', $encounter->id)->count());
    }

    #[Test]
    public function patient_encounter_history_is_maintained(): void
    {
        $this->actingAs($this->doctor);

        $patient = Patient::factory()->create();

        // Create multiple encounters
        for ($i = 0; $i < 3; $i++) {
            Encounter::create([
                'patient_id' => $patient->id,
                'status' => 'finished',
                'class' => 'AMB',
                'reason' => "Consulta {$i}",
            ]);
        }

        $this->assertEquals(3, $patient->encounters()->count());
    }
}
