<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HospitalWardFactory extends Factory
{
    protected $model = \App\Models\HospitalWard::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().' Ward',
            'type' => 'general',
            'total_beds' => 10,
            'floor' => (string) fake()->numberBetween(1, 5),
            'building' => 'Main',
            'is_active' => true,
        ];
    }
}
