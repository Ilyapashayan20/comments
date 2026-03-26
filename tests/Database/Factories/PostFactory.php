<?php

namespace Relaticle\Comments\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Relaticle\Comments\Tests\Models\Post;

class PostFactory extends Factory
{
    protected $model = Post::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
        ];
    }
}
