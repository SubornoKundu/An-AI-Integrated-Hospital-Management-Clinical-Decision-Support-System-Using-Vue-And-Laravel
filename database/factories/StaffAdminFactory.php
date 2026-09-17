<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class StaffAdminFactory extends Factory
{
    protected $model = \App\Models\StaffAdmin::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'username' => fake()->unique()->userName(),
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ];
    }
}
