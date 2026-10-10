<?php

namespace Tests\Feature\Livewire\Patients;

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

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'patients.create']);
        $role = Role::create(['name' => 'doctor']);
        $role->givePermissionTo('patients.create');

        $this->user = User::factory()->create();
        $this->user->assignRole('doctor');
    }

    #[Test]
    public function it_can_render_create_patient_form(): void
    {
        Livewire::actingAs($this->user)
            ->test('patients.create')
            ->assertStatus(200)
            ->assertSee('Nuevo Paciente');
    }

    #[Test]
    public function it_can_create_patient_with_valid_data(): void
    {
        Livewire::actingAs($this->user)
            ->test('patients.create')
            ->set('document_number', '12345678')
            ->set('first_name', 'Juan')
            ->set('last_name', 'Pérez')
            ->set('gender', 'male')
            ->set('birth_date', '1990-01-15')
            ->call('save')
            ->assertRedirect(route('patients.index'));

        $this->assertDatabaseHas('patients', [
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);
    }

    #[Test]
    public function it_validates_required_fields(): void
    {
        Livewire::actingAs($this->user)
            ->test('patients.create')
            ->set('document_number', '')
            ->set('first_name', '')
            ->set('last_name', '')
            ->call('save')
            ->assertHasErrors([
                'document_number',
                'first_name',
                'last_name',
            ]);
    }

    #[Test]
    public function it_validates_document_number_format(): void
    {
        Livewire::actingAs($this->user)
            ->test('patients.create')
            ->set('document_number', 'invalid-doc')
            ->set('first_name', 'Juan')
            ->set('last_name', 'Pérez')
            ->call('save')
            ->assertHasErrors(['document_number']);
    }

    #[Test]
    public function it_validates_unique_document_number(): void
    {
        Patient::factory()->create(['document_number' => '12345678']);

        Livewire::actingAs($this->user)
            ->test('patients.create')
            ->set('document_number', '12345678')
            ->set('first_name', 'Juan')
            ->set('last_name', 'Pérez')
            ->call('save')
            ->assertHasErrors(['document_number']);
    }

    #[Test]
    public function it_validates_birth_date_is_not_in_future(): void
    {
        Livewire::actingAs($this->user)
            ->test('patients.create')
            ->set('document_number', '12345678')
            ->set('first_name', 'Juan')
            ->set('last_name', 'Pérez')
            ->set('birth_date', now()->addDay()->format('Y-m-d'))
            ->call('save')
            ->assertHasErrors(['birth_date']);
    }

    #[Test]
    public function it_validates_gender_is_valid(): void
    {
        Livewire::actingAs($this->user)
            ->test('patients.create')
            ->set('document_number', '12345678')
            ->set('first_name', 'Juan')
            ->set('last_name', 'Pérez')
            ->set('gender', 'invalid')
            ->call('save')
            ->assertHasErrors(['gender']);
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        Livewire::test('patients.create')
            ->assertForbidden();
    }

    #[Test]
    public function it_requires_patients_create_permission(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('patients.create')
            ->assertForbidden();
    }

    #[Test]
    public function it_creates_fhir_resource_on_save(): void
    {
        Livewire::actingAs($this->user)
            ->test('patients.create')
            ->set('document_number', '12345678')
            ->set('first_name', 'Juan')
            ->set('last_name', 'Pérez')
            ->set('gender', 'male')
            ->set('birth_date', '1990-01-15')
            ->call('save');

        $patient = Patient::where('document_number', '12345678')->first();

        $this->assertNotNull($patient->fhir_resource);
        $this->assertEquals('Patient', $patient->fhir_resource['resourceType']);
    }

    #[Test]
    public function it_can_create_patient_with_optional_fields(): void
    {
        Livewire::actingAs($this->user)
            ->test('patients.create')
            ->set('document_number', '12345678')
            ->set('first_name', 'Juan')
            ->set('last_name', 'Pérez')
            ->set('gender', 'male')
            ->set('birth_date', '1990-01-15')
            ->set('email', 'juan@example.com')
            ->set('phone', '3001234567')
            ->set('address', 'Calle 123')
            ->set('city', 'Bogotá')
            ->call('save')
            ->assertRedirect(route('patients.index'));

        $this->assertDatabaseHas('patients', [
            'document_number' => '12345678',
            'city' => 'Bogotá',
        ]);
    }
}
