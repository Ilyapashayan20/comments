<?php

namespace Relaticle\Comments\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Relaticle\Comments\Tests\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => '$2y$04$x7FnGBxMRzQmMJDhKuOi6eLGOlIhWQOGl.IWxCNasFliYJXARljqe',
        ];
    }
}
