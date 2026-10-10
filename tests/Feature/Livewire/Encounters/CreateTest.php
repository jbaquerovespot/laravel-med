<?php

namespace Tests\Feature\Livewire\Encounters;

use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'encounters.create']);
        $role = Role::create(['name' => 'doctor']);
        $role->givePermissionTo('encounters.create');

        $this->user = User::factory()->create();
        $this->user->assignRole('doctor');

        $this->patient = Patient::factory()->create();
    }

    #[Test]
    public function it_can_render_encounter_form(): void
    {
        Livewire::actingAs($this->user)
            ->test('encounters.create')
            ->assertStatus(200)
            ->assertSee('Nueva Consulta');
    }

    #[Test]
    public function it_displays_multi_step_form(): void
    {
        Livewire::actingAs($this->user)
            ->test('encounters.create')
            ->assertSee('Paciente')
            ->assertSee('Motivo')
            ->assertSee('Signos Vitales')
            ->assertSee('Diagnóstico')
            ->assertSee('Tratamiento');
    }

    #[Test]
    public function it_can_navigate_between_steps(): void
    {
        Livewire::actingAs($this->user)
            ->test('encounters.create')
            ->set('patientId', $this->patient->id)
            ->call('nextStep')
            ->assertSet('currentStep', 2);
    }

    #[Test]
    public function it_can_go_to_previous_step(): void
    {
        Livewire::actingAs($this->user)
            ->test('encounters.create')
            ->set('patientId', $this->patient->id)
            ->set('currentStep', 3)
            ->call('previousStep')
            ->assertSet('currentStep', 2);
    }

    #[Test]
    public function it_requires_patient_selection_for_step_2(): void
    {
        Livewire::actingAs($this->user)
            ->test('encounters.create')
            ->set('currentStep', 1)
            ->call('nextStep')
            ->assertHasErrors(['patientId']);
    }

    #[Test]
    public function it_can_search_patients(): void
    {
        Livewire::actingAs($this->user)
            ->test('encounters.create')
            ->set('patientSearch', $this->patient->document_number)
            ->call('searchPatients')
            ->assertSee($this->patient->full_name);
    }

    #[Test]
    public function it_can_select_patient(): void
    {
        Livewire::actingAs($this->user)
            ->test('encounters.create')
            ->call('selectPatient', $this->patient->id)
            ->assertSet('patientId', $this->patient->id)
            ->assertSet('currentStep', 2);
    }

    #[Test]
    public function it_validates_chief_complaint_for_step_3(): void
    {
        Livewire::actingAs($this->user)
            ->test('encounters.create')
            ->set('patientId', $this->patient->id)
            ->set('currentStep', 2)
            ->set('chiefComplaint', '')  // Empty
            ->call('nextStep')
            ->assertHasErrors(['chiefComplaint']);
    }

    #[Test]
    public function it_can_calculate_bmi(): void
    {
        Livewire::actingAs($this->user)
            ->test('encounters.create')
            ->set('weight', '70')
            ->set('height', '175')
            ->call('calculateBmi')
            ->assertSet('bmi', 22.9);
    }

    #[Test]
    public function it_can_add_diagnosis(): void
    {
        Livewire::actingAs($this->user)
            ->test('encounters.create')
            ->set('selectedDiagnosisCode', 'J45')
            ->set('selectedDiagnosisDisplay', 'Asma bronquial')
            ->set('diagnosisType', 'principal')
            ->call('addDiagnosis')
            ->assertSee('J45')
            ->assertSee('Asma bronquial');
    }

    #[Test]
    public function it_can_remove_diagnosis(): void
    {
        Livewire::actingAs($this->user)
            ->test('encounters.create')
            ->set('selectedDiagnosisCode', 'J45')
            ->set('selectedDiagnosisDisplay', 'Asma bronquial')
            ->call('addDiagnosis')
            ->call('removeDiagnosis', 0)
            ->assertDontSee('J45');
    }

    #[Test]
    public function it_can_add_medication(): void
    {
        Livewire::actingAs($this->user)
            ->test('encounters.create')
            ->set('patientId', $this->patient->id)
            ->set('selectedMedication', 'Acetaminofén')
            ->set('dosage', '500mg')
            ->set('frequency', 'cada 6 horas')
            ->set('duration', '5 días')
            ->call('addMedication')
            ->assertSee('Acetaminofén');
    }

    #[Test]
    public function it_creates_encounter_on_submit(): void
    {
        Livewire::actingAs($this->user)
            ->test('encounters.create')
            ->set('patientId', $this->patient->id)
            ->set('chiefComplaint', 'Dolor de cabeza')
            ->set('anamnesis', 'Paciente con cefalea frontal')
            ->set('systolicBp', '120')
            ->set('diastolicBp', '80')
            ->call('submit')
            ->assertRedirect();

        $this->assertDatabaseHas('encounters', [
            'patient_id' => $this->patient->id,
            'reason' => 'Dolor de cabeza',
        ]);
    }

    #[Test]
    public function it_creates_observations_for_vital_signs(): void
    {
        Livewire::actingAs($this->user)
            ->test('encounters.create')
            ->set('patientId', $this->patient->id)
            ->set('chiefComplaint', 'Consulta general')
            ->set('systolicBp', '120')
            ->set('diastolicBp', '80')
            ->set('heartRate', '72')
            ->call('submit');

        $encounter = Encounter::where('patient_id', $this->patient->id)->first();

        $this->assertDatabaseHas('observations', [
            'patient_id' => $this->patient->id,
            'encounter_id' => $encounter->id,
            'code' => '8480-6', // Systolic BP LOINC code
        ]);
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        Livewire::test('encounters.create')
            ->assertForbidden();
    }

    #[Test]
    public function it_requires_encounters_create_permission(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('encounters.create')
            ->assertForbidden();
    }
}
