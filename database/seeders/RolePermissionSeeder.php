<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions
        $permissions = [
            // Patient permissions
            'patients.view',
            'patients.create',
            'patients.edit',
            'patients.delete',

            // Encounter permissions
            'encounters.view',
            'encounters.create',
            'encounters.edit',
            'encounters.finish',

            // Clinical permissions
            'conditions.view',
            'conditions.create',
            'observations.view',
            'observations.create',
            'medications.view',
            'medications.prescribe',
            'laboratory.view',
            'laboratory.order',

            // RDA permissions
            'rda.view',
            'rda.generate',

            // Admin permissions
            'users.manage',
            'roles.manage',
            'settings.manage',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Define roles and their permissions
        $roles = [
            'admin' => $permissions, // Admin gets all permissions
            'doctor' => [
                'patients.view',
                'patients.create',
                'patients.edit',
                'encounters.view',
                'encounters.create',
                'encounters.edit',
                'encounters.finish',
                'conditions.view',
                'conditions.create',
                'observations.view',
                'observations.create',
                'medications.view',
                'medications.prescribe',
                'laboratory.view',
                'laboratory.order',
                'rda.view',
                'rda.generate',
            ],
            'nurse' => [
                'patients.view',
                'patients.create',
                'encounters.view',
                'observations.view',
                'observations.create',
                'medications.view',
                'rda.view',
            ],
            'receptionist' => [
                'patients.view',
                'patients.create',
                'patients.edit',
                'encounters.view',
                'rda.view',
            ],
        ];

        // Create roles and assign permissions
        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }

        $this->command->info('Roles and permissions seeded successfully.');
    }
}
