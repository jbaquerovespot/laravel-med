<?php

namespace Tests\Feature\Livewire\Patients;

use App\Models\Condition;
use App\Models\Encounter;
use App\Models\MedicationRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'patients.view']);
        $role = Role::create(['name' => 'doctor']);
        $role->givePermissionTo('patients.view');

        $this->user = User::factory()->create();
        $this->user->assignRole('doctor');
    }

    #[Test]
    public function it_can_render_patient_detail(): void
    {
        $patient = Patient::factory()->create();

        Livewire::actingAs($this->user)
            ->test('patients.show', ['id' => $patient->id])
            ->assertStatus(200)
            ->assertSee($patient->full_name);
    }

    #[Test]
    public function it_displays_patient_demographics(): void
    {
        $patient = Patient::factory()->create([
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'gender' => 'male',
            'birth_date' => '1990-01-15',
        ]);

        Livewire::actingAs($this->user)
            ->test('patients.show', ['id' => $patient->id])
            ->assertSee('12345678')
            ->assertSee('Juan')
            ->assertSee('Pérez')
            ->assertSee('Masculino');
    }

    #[Test]
    public function it_displays_encounter_history(): void
    {
        $patient = Patient::factory()->create();
        $encounter = Encounter::create([
            'patient_id' => $patient->id,
            'status' => 'finished',
            'class' => 'AMB',
            'reason' => 'Dolor de cabeza',
        ]);

        Livewire::actingAs($this->user)
            ->test('patients.show', ['id' => $patient->id])
            ->assertSee('Dolor de cabeza')
            ->assertSee('Consultas');
    }

    #[Test]
    public function it_displays_active_conditions(): void
    {
        $patient = Patient::factory()->create();
        Condition::create([
            'patient_id' => $patient->id,
            'code' => 'J45',
            'note' => 'Asma bronquial',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'category' => 'principal',
        ]);

        Livewire::actingAs($this->user)
            ->test('patients.show', ['id' => $patient->id])
            ->assertSee('Asma bronquial')
            ->assertSee('Diagnósticos');
    }

    #[Test]
    public function it_displays_medications(): void
    {
        $patient = Patient::factory()->create();
        MedicationRequest::create([
            'patient_id' => $patient->id,
            'medication' => 'Salbutamol',
            'dosage' => '100mcg',
            'frequency' => 'cada 6 horas',
            'duration' => '30 días',
            'status' => 'active',
        ]);

        Livewire::actingAs($this->user)
            ->test('patients.show', ['id' => $patient->id])
            ->assertSee('Salbutamol')
            ->assertSee('Medicamentos');
    }

    #[Test]
    public function it_shows_404_for_non_existent_patient(): void
    {
        Livewire::actingAs($this->user)
            ->test('patients.show', ['id' => 99999])
            ->assertStatus(404);
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        $patient = Patient::factory()->create();

        Livewire::test('patients.show', ['id' => $patient->id])
            ->assertForbidden();
    }

    #[Test]
    public function it_requires_patients_view_permission(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        Livewire::actingAs($user)
            ->test('patients.show', ['id' => $patient->id])
            ->assertForbidden();
    }

    #[Test]
    public function it_displays_empty_state_for_no_encounters(): void
    {
        $patient = Patient::factory()->create();

        Livewire::actingAs($this->user)
            ->test('patients.show', ['id' => $patient->id])
            ->assertSee('No hay consultas');
    }

    #[Test]
    public function it_displays_patient_age(): void
    {
        $patient = Patient::factory()->create([
            'birth_date' => now()->subYears(30)->format('Y-m-d'),
        ]);

        Livewire::actingAs($this->user)
            ->test('patients.show', ['id' => $patient->id])
            ->assertSee('30 años');
    }
}
