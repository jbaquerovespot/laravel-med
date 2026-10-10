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

class IndexTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'patients.view']);

        // Create role
        $role = Role::create(['name' => 'receptionist']);
        $role->givePermissionTo('patients.view');

        // Create user
        $this->user = User::factory()->create();
        $this->user->assignRole('receptionist');
    }

    #[Test]
    public function it_can_render_patients_list(): void
    {
        Livewire::actingAs($this->user)
            ->test('patients.index')
            ->assertStatus(200)
            ->assertSee('Pacientes');
    }

    #[Test]
    public function it_displays_patients(): void
    {
        $patients = Patient::factory()->count(3)->create();

        Livewire::actingAs($this->user)
            ->test('patients.index')
            ->assertSee($patients[0]->full_name)
            ->assertSee($patients[1]->full_name)
            ->assertSee($patients[2]->full_name);
    }

    #[Test]
    public function it_can_search_by_document_number(): void
    {
        $patient1 = Patient::factory()->create(['document_number' => '12345678']);
        $patient2 = Patient::factory()->create(['document_number' => '87654321']);

        Livewire::actingAs($this->user)
            ->test('patients.index')
            ->set('search', '12345678')
            ->assertSee($patient1->full_name)
            ->assertDontSee($patient2->full_name);
    }

    #[Test]
    public function it_can_search_by_first_name(): void
    {
        $patient1 = Patient::factory()->create(['first_name' => 'Juan', 'last_name' => 'García']);
        $patient2 = Patient::factory()->create(['first_name' => 'María', 'last_name' => 'López']);

        Livewire::actingAs($this->user)
            ->test('patients.index')
            ->set('search', 'Juan')
            ->assertSee('Juan')
            ->assertDontSee('María');
    }

    #[Test]
    public function it_can_search_by_last_name(): void
    {
        $patient1 = Patient::factory()->create(['first_name' => 'Juan', 'last_name' => 'García']);
        $patient2 = Patient::factory()->create(['first_name' => 'María', 'last_name' => 'López']);

        Livewire::actingAs($this->user)
            ->test('patients.index')
            ->set('search', 'García')
            ->assertSee('García')
            ->assertDontSee('López');
    }

    #[Test]
    public function it_shows_empty_state_when_no_results(): void
    {
        Patient::factory()->create();

        Livewire::actingAs($this->user)
            ->test('patients.index')
            ->set('search', 'NonExistentPatient')
            ->assertSee('No hay pacientes');
    }

    #[Test]
    public function it_paginates_results(): void
    {
        Patient::factory()->count(25)->create();

        Livewire::actingAs($this->user)
            ->test('patients.index')
            ->assertViewHas('patients', function ($patients) {
                return $patients->count() === 20; // Default pagination
            });
    }

    #[Test]
    public function it_resets_page_when_search_changes(): void
    {
        Patient::factory()->count(30)->create();

        $component = Livewire::actingAs($this->user)
            ->test('patients.index');

        // Go to page 2
        $component->call('gotoPage', 2);

        // Search should reset to page 1
        $component->set('search', 'test')
            ->assertSet('page', 1);
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        Livewire::test('patients.index')
            ->assertForbidden();
    }

    #[Test]
    public function it_requires_patients_view_permission(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('patients.index')
            ->assertForbidden();
    }
}
