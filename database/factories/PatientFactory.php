<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_number' => (string) fake()->unique()->numberBetween(10000000, 9999999999),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'gender' => fake()->randomElement(['male', 'female']),
            'birth_date' => fake()->date('Y-m-d', '-18 years'),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('3#########'),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => 'Colombia',
            'active' => true,
        ];
    }

    /**
     * Indicate that the patient is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Indicate that the patient is a minor.
     */
    public function minor(): static
    {
        return $this->state(fn (array $attributes) => [
            'birth_date' => fake()->date('Y-m-d', '-10 years'),
        ]);
    }

    /**
     * Indicate that the patient is elderly.
     */
    public function elderly(): static
    {
        return $this->state(fn (array $attributes) => [
            'birth_date' => fake()->date('Y-m-d', '-70 years'),
        ]);
    }
}
