<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Professional;
use App\Models\Therapy;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'day_of_week' => fake()->word(),
            'start_time' => fake()->time(),
            'end_time' => fake()->time(),
            'patient_id' => Patient::factory(),
            'professional_id' => Professional::factory(),
            'therapy_id' => Therapy::factory(),
            'unit_id' => Unit::factory(),
        ];
    }
}
