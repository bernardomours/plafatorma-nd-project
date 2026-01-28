<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'birth_date' => fake()->date(),
            'cpf' => fake()->word(),
            'guardian_name' => fake()->word(),
            'guardian_phone' => fake()->word(),
            'unit_id' => Unit::factory(),
        ];
    }
}
