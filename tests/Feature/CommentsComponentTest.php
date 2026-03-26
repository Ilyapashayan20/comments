<?php

use Livewire\Livewire;
use Relaticle\Comments\Comment;
use Relaticle\Comments\Livewire\Comments;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('allows authenticated user to create a comment on a post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    Livewire::test(Comments::class, ['model' => $post])
        ->set('newComment', '<p>Hello World</p>')
        ->call('addComment')
        ->assertSet('newComment', '');

    expect(Comment::count())->toBe(1);
    expect(Comment::first()->body)->toBe('<p>Hello World</p>');
});

it('associates new comment with the authenticated user', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    Livewire::test(Comments::class, ['model' => $post])
        ->set('newComment', '<p>Test</p>')
        ->call('addComment');

    $comment = Comment::first();

    expect($comment->user_id)->toBe($user->id);
    expect($comment->user_type)->toBe($user->getMorphClass());
    expect($comment->commentable_id)->toBe($post->id);
    expect($comment->commentable_type)->toBe($post->getMorphClass());
});

it('requires authentication to create a comment', function () {
    $post = Post::factory()->create();

    Livewire::test(Comments::class, ['model' => $post])
        ->set('newComment', '<p>Hello</p>')
        ->call('addComment')
        ->assertForbidden();
});

it('validates that comment body is not empty', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    Livewire::test(Comments::class, ['model' => $post])
        ->set('newComment', '')
        ->call('addComment')
        ->assertHasErrors('newComment');

    expect(Comment::count())->toBe(0);
});

it('paginates top-level comments with load more', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    config(['comments.pagination.per_page' => 5]);

    Comment::factory()->count(12)->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Comments::class, ['model' => $post]);

    expect($component->get('loadedCount'))->toBe(5);

    $component->call('loadMore');

    expect($component->get('loadedCount'))->toBe(10);

    $component->call('loadMore');

    expect($component->get('loadedCount'))->toBe(15);
});

it('hides load more button when all comments are loaded', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    config(['comments.pagination.per_page' => 10]);

    Comment::factory()->count(5)->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    $this->actingAs($user);

    Livewire::test(Comments::class, ['model' => $post])
        ->assertDontSee('Load more comments');
});

it('toggles sort direction between asc and desc', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    Livewire::test(Comments::class, ['model' => $post])
        ->assertSet('sortDirection', 'asc')
        ->call('toggleSort')
        ->assertSet('sortDirection', 'desc')
        ->call('toggleSort')
        ->assertSet('sortDirection', 'asc');
});

it('returns comments in correct sort order via computed property', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $older = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'body' => '<p>Older comment</p>',
        'created_at' => now()->subHour(),
    ]);

    $newer = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'body' => '<p>Newer comment</p>',
        'created_at' => now(),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Comments::class, ['model' => $post]);

    $comments = $component->instance()->comments();
    expect($comments->first()->id)->toBe($older->id);
    expect($comments->last()->id)->toBe($newer->id);

    $component->call('toggleSort');

    $comments = $component->instance()->comments();
    expect($comments->first()->id)->toBe($newer->id);
    expect($comments->last()->id)->toBe($older->id);
});

it('displays total comment count', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    Comment::factory()->count(3)->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    $this->actingAs($user);

    Livewire::test(Comments::class, ['model' => $post])
        ->assertSee('Comments (3)');
});

it('hides comment form for guests', function () {
    $post = Post::factory()->create();

    Livewire::test(Comments::class, ['model' => $post])
        ->assertDontSee('Write a comment...');
});
