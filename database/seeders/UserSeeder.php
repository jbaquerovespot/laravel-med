<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@vespot.co'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Admin123!'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        // Create default doctor user
        $doctor = User::firstOrCreate(
            ['email' => 'doctor@vespot.co'],
            [
                'name' => 'Dr. Juan Pérez',
                'password' => Hash::make('Doctor123!'),
                'email_verified_at' => now(),
            ]
        );
        $doctor->assignRole('doctor');

        // Create default nurse user
        $nurse = User::firstOrCreate(
            ['email' => 'enfermera@vespot.co'],
            [
                'name' => 'Enf. María García',
                'password' => Hash::make('Nurse123!'),
                'email_verified_at' => now(),
            ]
        );
        $nurse->assignRole('nurse');

        // Create default receptionist user
        $receptionist = User::firstOrCreate(
            ['email' => 'recepcion@vespot.co'],
            [
                'name' => 'Recepcionista',
                'password' => Hash::make('Reception123!'),
                'email_verified_at' => now(),
            ]
        );
        $receptionist->assignRole('receptionist');

        $this->command->info('Default users seeded successfully.');
        $this->command->info('Admin: admin@vespot.co / Admin123!');
        $this->command->info('Doctor: doctor@vespot.co / Doctor123!');
        $this->command->info('Nurse: enfermera@vespot.co / Nurse123!');
        $this->command->info('Receptionist: recepcion@vespot.co / Reception123!');
    }
}
