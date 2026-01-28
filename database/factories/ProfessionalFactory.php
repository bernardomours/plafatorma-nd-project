<?php

namespace Database\Factories;

use App\Models\Therapy;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfessionalFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'cpf' => fake()->word(),
            'phone' => fake()->phoneNumber(),
            'birth_date' => fake()->date(),
            'register_number' => fake()->word(),
            'therapy_id' => Therapy::factory(),
        ];
    }
}
