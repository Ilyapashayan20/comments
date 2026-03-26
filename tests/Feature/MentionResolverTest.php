<?php

use Relaticle\Comments\Contracts\MentionResolver;
use Relaticle\Comments\Mentions\DefaultMentionResolver;
use Relaticle\Comments\Tests\Models\User;

it('resolves the default mention resolver from the container', function () {
    $resolver = app(MentionResolver::class);

    expect($resolver)->toBeInstanceOf(DefaultMentionResolver::class);
});

it('searches users by name prefix', function () {
    User::factory()->create(['name' => 'john']);
    User::factory()->create(['name' => 'joe']);
    User::factory()->create(['name' => 'alice']);

    $resolver = app(MentionResolver::class);
    $results = $resolver->search('jo');

    expect($results)->toHaveCount(2)
        ->each->toBeInstanceOf(User::class);
});

it('limits search results to configured max', function () {
    for ($i = 0; $i < 10; $i++) {
        User::factory()->create(['name' => "john{$i}"]);
    }

    config()->set('comments.mentions.max_results', 3);

    $resolver = new DefaultMentionResolver;
    $results = $resolver->search('john');

    expect($results)->toHaveCount(3);
});

it('resolves users by exact names', function () {
    $john = User::factory()->create(['name' => 'john']);
    $jane = User::factory()->create(['name' => 'jane']);
    User::factory()->create(['name' => 'alice']);

    $resolver = app(MentionResolver::class);
    $users = $resolver->resolveByNames(['john', 'jane']);

    expect($users)->toHaveCount(2);
    expect($users->pluck('id')->all())
        ->toContain($john->id)
        ->toContain($jane->id);
});

it('returns empty collection for unknown names', function () {
    $resolver = app(MentionResolver::class);
    $users = $resolver->resolveByNames(['nobody', 'nonexistent']);

    expect($users)->toBeEmpty();
});
