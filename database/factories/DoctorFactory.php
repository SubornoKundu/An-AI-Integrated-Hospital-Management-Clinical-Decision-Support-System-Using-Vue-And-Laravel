<?php

namespace Database\Factories;

use App\Models\DoctorCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class DoctorFactory extends Factory
{
    protected $model = \App\Models\Doctor::class;

    public function definition(): array
    {
        return [
            'name' => 'Dr. '.fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password123'),
            'category_id' => DoctorCategory::factory(),
            'consultancy_fee' => fake()->randomElement([500, 800, 1000, 1500]),
            'bio' => fake()->sentence(),
            'phone' => fake()->numerify('01#########'),
            'is_active' => true,
            'gender' => fake()->randomElement(['Male', 'Female', 'Other']),
        ];
    }
}
