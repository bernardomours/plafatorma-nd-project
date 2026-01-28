<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'cnpj' => fake()->word(),
            'street' => fake()->streetName(),
            'neighborhood' => fake()->word(),
            'number' => fake()->word(),
        ];
    }
}
