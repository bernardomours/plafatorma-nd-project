<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Professional;
use App\Models\Therapy;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'appointment_date' => fake()->date(),
            'check_in' => fake()->time(),
            'check_out' => fake()->time(),
            'service_type' => fake()->word(),
            'session_number' => fake()->numberBetween(-10000, 10000),
            'patient_id' => Patient::factory(),
            'professional_id' => Professional::factory(),
            'therapy_id' => Therapy::factory(),
            'unit_id' => Unit::factory(),
        ];
    }
}
