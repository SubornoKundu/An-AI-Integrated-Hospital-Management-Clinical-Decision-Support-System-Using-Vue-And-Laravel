<?php

namespace Database\Factories;

use App\Models\HospitalWard;
use Illuminate\Database\Eloquent\Factories\Factory;

class HospitalBedFactory extends Factory
{
    protected $model = \App\Models\HospitalBed::class;

    public function definition(): array
    {
        return [
            'ward_id' => HospitalWard::factory(),
            'bed_number' => 'B-'.fake()->unique()->numberBetween(100, 999),
            'bed_type' => 'standard',
            'status' => 'available',
            'daily_charge' => fake()->randomElement([1000, 1500, 2000]),
        ];
    }
}
