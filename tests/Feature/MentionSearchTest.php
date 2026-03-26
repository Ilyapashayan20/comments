<?php

use Livewire\Livewire;
use Relaticle\Comments\Comment;
use Relaticle\Comments\Livewire\CommentItem;
use Relaticle\Comments\Livewire\Comments;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('returns matching users for search query', function () {
    $alice = User::factory()->create(['name' => 'Alice']);
    User::factory()->create(['name' => 'Bob']);
    $post = Post::factory()->create();

    $this->actingAs($alice);

    $component = Livewire::test(Comments::class, ['model' => $post]);
    $results = $component->instance()->searchUsers('Ali');

    expect($results)->toHaveCount(1);
    expect($results[0])->toMatchArray([
        'id' => $alice->id,
        'name' => 'Alice',
    ]);
    expect($results[0])->toHaveKey('avatar_url');
});

it('returns empty array for empty query', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(Comments::class, ['model' => $post]);
    $results = $component->instance()->searchUsers('');

    expect($results)->toBeEmpty();
});

it('returns empty array for no matches', function () {
    $user = User::factory()->create(['name' => 'Alice']);
    $post = Post::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(Comments::class, ['model' => $post]);
    $results = $component->instance()->searchUsers('zzz');

    expect($results)->toBeEmpty();
});

it('limits search results to configured max', function () {
    $user = User::factory()->create(['name' => 'Admin']);
    $post = Post::factory()->create();

    for ($i = 1; $i <= 10; $i++) {
        User::factory()->create(['name' => "Test User {$i}"]);
    }

    config(['comments.mentions.max_results' => 3]);

    $this->actingAs($user);

    $component = Livewire::test(Comments::class, ['model' => $post]);
    $results = $component->instance()->searchUsers('Test');

    expect($results)->toHaveCount(3);
});

it('stores mentions when creating comment with @mention', function () {
    $user = User::factory()->create();
    $alice = User::factory()->create(['name' => 'Alice']);
    $post = Post::factory()->create();

    $this->actingAs($user);

    Livewire::test(Comments::class, ['model' => $post])
        ->set('newComment', '<p>Hey @Alice check this</p>')
        ->call('addComment');

    $comment = Comment::first();

    expect($comment->mentions)->toHaveCount(1);
    expect($comment->mentions->first()->id)->toBe($alice->id);
});

it('stores mentions when editing comment with @mention', function () {
    $user = User::factory()->create();
    $bob = User::factory()->create(['name' => 'Bob']);
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'body' => '<p>Original comment</p>',
    ]);

    $this->actingAs($user);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('startEdit')
        ->set('editBody', '<p>Updated @Bob</p>')
        ->call('saveEdit');

    $comment->refresh();

    expect($comment->mentions)->toHaveCount(1);
    expect($comment->mentions->first()->id)->toBe($bob->id);
});
