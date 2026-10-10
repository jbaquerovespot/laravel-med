<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $doctor;

    private User $nurse;

    private User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();

        // Create all permissions
        $permissions = [
            'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
            'encounters.view', 'encounters.create', 'encounters.edit', 'encounters.finish',
            'conditions.view', 'conditions.create',
            'observations.view', 'observations.create',
            'medications.view', 'medications.prescribe',
            'laboratory.view', 'laboratory.order',
            'rda.view', 'rda.generate',
            'users.manage', 'roles.manage', 'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles with permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $doctorRole = Role::create(['name' => 'doctor']);
        $doctorRole->givePermissionTo([
            'patients.view', 'patients.create', 'patients.edit',
            'encounters.view', 'encounters.create', 'encounters.edit', 'encounters.finish',
            'conditions.view', 'conditions.create',
            'observations.view', 'observations.create',
            'medications.view', 'medications.prescribe',
            'laboratory.view', 'laboratory.order',
            'rda.view', 'rda.generate',
        ]);

        $nurseRole = Role::create(['name' => 'nurse']);
        $nurseRole->givePermissionTo([
            'patients.view', 'patients.create',
            'encounters.view', 'encounters.create',
            'observations.view', 'observations.create',
            'medications.view',
        ]);

        $receptionistRole = Role::create(['name' => 'receptionist']);
        $receptionistRole->givePermissionTo([
            'patients.view', 'patients.create', 'patients.edit',
        ]);

        // Create users with roles
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->doctor = User::factory()->create();
        $this->doctor->assignRole('doctor');

        $this->nurse = User::factory()->create();
        $this->nurse->assignRole('nurse');

        $this->receptionist = User::factory()->create();
        $this->receptionist->assignRole('receptionist');
    }

    #[Test]
    public function doctor_can_create_encounter(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->actingAs($this->doctor)
            ->get(route('encounters.create'));

        $response->assertStatus(200);
    }

    #[Test]
    public function receptionist_cannot_create_encounter(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->get(route('encounters.create'));

        $response->assertForbidden();
    }

    #[Test]
    public function receptionist_cannot_prescribe_medication(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->actingAs($this->receptionist)
            ->get(route('medications.prescribe', $patient->id));

        $response->assertForbidden();
    }

    #[Test]
    public function doctor_can_prescribe_medication(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->actingAs($this->doctor)
            ->get(route('medications.prescribe', $patient->id));

        $response->assertStatus(200);
    }

    #[Test]
    public function admin_can_manage_users(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $this->assertTrue($this->admin->can('users.manage'));
    }

    #[Test]
    public function doctor_cannot_manage_users(): void
    {
        $this->assertFalse($this->doctor->can('users.manage'));
    }

    #[Test]
    public function nurse_can_view_patients(): void
    {
        $response = $this->actingAs($this->nurse)
            ->get(route('patients.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function nurse_cannot_edit_patients(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->actingAs($this->nurse)
            ->get(route('patients.edit', $patient->id));

        $response->assertForbidden();
    }

    #[Test]
    public function doctor_can_generate_rda(): void
    {
        $patient = Patient::factory()->create();
        $encounter = Encounter::create([
            'patient_id' => $patient->id,
            'status' => 'finished',
            'class' => 'AMB',
            'reason' => 'Consulta general',
        ]);

        $response = $this->actingAs($this->doctor)
            ->get(route('rda.generate', $encounter->id));

        $response->assertStatus(200);
    }

    #[Test]
    public function receptionist_cannot_generate_rda(): void
    {
        $patient = Patient::factory()->create();
        $encounter = Encounter::create([
            'patient_id' => $patient->id,
            'status' => 'finished',
            'class' => 'AMB',
            'reason' => 'Consulta general',
        ]);

        $response = $this->actingAs($this->receptionist)
            ->get(route('rda.generate', $encounter->id));

        $response->assertForbidden();
    }

    #[Test]
    public function doctor_can_order_laboratory(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->actingAs($this->doctor)
            ->get(route('laboratory.order', $patient->id));

        $response->assertStatus(200);
    }

    #[Test]
    public function receptionist_cannot_order_laboratory(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->actingAs($this->receptionist)
            ->get(route('laboratory.order', $patient->id));

        $response->assertForbidden();
    }

    #[Test]
    public function unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function user_role_is_displayed_in_layout(): void
    {
        $response = $this->actingAs($this->doctor)
            ->get(route('dashboard'));

        $response->assertSee('doctor');
    }

    #[Test]
    public function admin_sees_admin_menu(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('dashboard'));

        $response->assertSee('Administración');
    }

    #[Test]
    public function doctor_does_not_see_admin_menu(): void
    {
        $response = $this->actingAs($this->doctor)
            ->get(route('dashboard'));

        $response->assertDontSee('Administración');
    }
}
