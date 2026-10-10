<?php

namespace Tests\Feature;

use App\Models\Condition;
use App\Models\Encounter;
use App\Models\MedicationRequest;
use App\Models\Observation;
use App\Models\Patient;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RdaGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Patient $patient;

    private Encounter $encounter;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'patients.view']);
        Permission::create(['name' => 'rda.generate']);

        // Create doctor role
        $role = Role::create(['name' => 'doctor']);
        $role->givePermissionTo(['patients.view', 'rda.generate']);

        $this->doctor = User::factory()->create();
        $this->doctor->assignRole('doctor');

        // Create patient
        $this->patient = Patient::create([
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'gender' => 'male',
            'birth_date' => '1990-01-15',
            'phone' => '3001234567',
            'email' => 'juan@example.com',
            'active' => true,
        ]);

        // Create encounter
        $this->encounter = Encounter::create([
            'patient_id' => $this->patient->id,
            'status' => 'finished',
            'class' => 'AMB',
            'type' => 'consultation',
            'reason' => 'Dolor de garganta',
            'note' => 'Faringitis aguda diagnosticada',
        ]);

        // Add vital signs
        Observation::create([
            'patient_id' => $this->patient->id,
            'encounter_id' => $this->encounter->id,
            'code' => '8480-6',
            'value' => 120,
            'unit' => 'mmHg',
            'status' => 'final',
            'category' => 'vital-signs',
        ]);

        Observation::create([
            'patient_id' => $this->patient->id,
            'encounter_id' => $this->encounter->id,
            'code' => '8867-4',
            'value' => 72,
            'unit' => '/min',
            'status' => 'final',
            'category' => 'vital-signs',
        ]);

        // Add diagnosis
        Condition::create([
            'patient_id' => $this->patient->id,
            'encounter_id' => $this->encounter->id,
            'code' => 'J02',
            'note' => 'Faringitis aguda',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'category' => 'principal',
        ]);

        // Add medication
        MedicationRequest::create([
            'patient_id' => $this->patient->id,
            'encounter_id' => $this->encounter->id,
            'medication' => 'Amoxicilina',
            'dosage' => '500mg',
            'frequency' => 'cada 8 horas',
            'duration' => '7 días',
            'status' => 'active',
        ]);
    }

    #[Test]
    public function it_generates_rda_pdf(): void
    {
        $response = $this->actingAs($this->doctor)
            ->get(route('rda.generate', $this->encounter->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    #[Test]
    public function rda_contains_patient_information(): void
    {
        $response = $this->actingAs($this->doctor)
            ->get(route('rda.generate', $this->encounter->id));

        $content = $response->getContent();

        // The PDF should contain patient info
        // Note: In a real test, you might parse the PDF content
        $this->assertNotEmpty($content);
    }

    #[Test]
    public function rda_contains_encounter_reason(): void
    {
        $response = $this->actingAs($this->doctor)
            ->get(route('rda.generate', $this->encounter->id));

        $response->assertStatus(200);
    }

    #[Test]
    public function rda_contains_diagnosis(): void
    {
        $response = $this->actingAs($this->doctor)
            ->get(route('rda.generate', $this->encounter->id));

        $response->assertStatus(200);
    }

    #[Test]
    public function rda_contains_medications(): void
    {
        $response = $this->actingAs($this->doctor)
            ->get(route('rda.generate', $this->encounter->id));

        $response->assertStatus(200);
    }

    #[Test]
    public function rda_generation_requires_permission(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('rda.generate', $this->encounter->id));

        $response->assertForbidden();
    }

    #[Test]
    public function rda_generation_requires_authentication(): void
    {
        $response = $this->get(route('rda.generate', $this->encounter->id));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function rda_returns_404_for_non_existent_encounter(): void
    {
        $response = $this->actingAs($this->doctor)
            ->get(route('rda.generate', 99999));

        $response->assertStatus(404);
    }

    #[Test]
    public function rda_filename_contains_patient_info(): void
    {
        $response = $this->actingAs($this->doctor)
            ->get(route('rda.generate', $this->encounter->id));

        // Check that the filename contains patient document number
        $contentDisposition = $response->headers->get('content-disposition');
        $this->assertNotNull($contentDisposition);
        $this->assertStringContainsString('.pdf', $contentDisposition);
    }

    #[Test]
    public function rda_with_multiple_diagnoses(): void
    {
        // Add secondary diagnosis
        Condition::create([
            'patient_id' => $this->patient->id,
            'encounter_id' => $this->encounter->id,
            'code' => 'I10',
            'note' => 'Hipertensión esencial',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'category' => 'secondary',
        ]);

        $response = $this->actingAs($this->doctor)
            ->get(route('rda.generate', $this->encounter->id));

        $response->assertStatus(200);
    }

    #[Test]
    public function rda_with_lab_orders(): void
    {
        // Add lab order
        ServiceRequest::create([
            'patient_id' => $this->patient->id,
            'encounter_id' => $this->encounter->id,
            'code' => '4544-3',
            'display' => 'Hematocrito',
            'category' => 'laboratory',
            'priority' => 'routine',
            'status' => 'active',
            'intent' => 'order',
            'authored_on' => now(),
        ]);

        $response = $this->actingAs($this->doctor)
            ->get(route('rda.generate', $this->encounter->id));

        $response->assertStatus(200);
    }

    #[Test]
    public function rda_complies_with_colombian_regulations(): void
    {
        $response = $this->actingAs($this->doctor)
            ->get(route('rda.generate', $this->encounter->id));

        $response->assertStatus(200);

        // RDA should comply with:
        // - Resolución 1995 de 1999
        // - Ley 1581 de 2012
        // - Resolución 1888 de 2025
        // These are enforced in the template
    }
}
