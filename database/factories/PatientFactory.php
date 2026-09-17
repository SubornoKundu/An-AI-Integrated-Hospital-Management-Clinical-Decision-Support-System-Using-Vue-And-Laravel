<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class PatientFactory extends Factory
{
    protected $model = \App\Models\Patient::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'contact' => fake()->numerify('01#########'),
            'password' => Hash::make('password123'),
            'gender' => fake()->randomElement(['Male', 'Female', 'Other']),
            'date_of_birth' => fake()->date(),
            'blood_group' => fake()->randomElement(['A+', 'B+', 'O+', 'AB+']),
            'address' => fake()->address(),
        ];
    }
}
