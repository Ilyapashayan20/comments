<?php

namespace Relaticle\Comments\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Relaticle\Comments\Models\Comment;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'body' => '<p>'.fake()->paragraph().'</p>',
            'edited_at' => null,
        ];
    }

    public function withParent(?Comment $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent?->id,
        ]);
    }

    public function edited(): static
    {
        return $this->state(fn (array $attributes) => [
            'edited_at' => now(),
        ]);
    }
}
