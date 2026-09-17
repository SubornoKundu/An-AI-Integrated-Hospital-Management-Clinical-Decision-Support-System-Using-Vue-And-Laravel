<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorCategoryFactory extends Factory
{
    protected $model = \App\Models\DoctorCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Cardiology', 'Neurology', 'Pediatrics', 'Orthopedics',
                'Dermatology', 'General Medicine', 'ENT', 'Gynecology',
            ]).' '.fake()->unique()->numerify('##'),
            'icon' => '🩺',
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
