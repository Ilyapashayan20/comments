<?php

use Livewire\Livewire;
use Relaticle\Comments\Livewire\CommentItem;
use Relaticle\Comments\Models\Comment;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('renders mention with styled span', function () {
    $user = User::factory()->create();
    $alice = User::factory()->create(['name' => 'Alice']);
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '@Alice said hi',
    ]);

    $comment->mentions()->attach($alice->id, ['commenter_type' => $alice->getMorphClass()]);

    $rendered = $comment->renderBodyWithMentions();

    expect($rendered)->toContain('comment-mention');
    expect($rendered)->toContain('@Alice</span>');
});

it('renders multiple mentions with styled spans', function () {
    $user = User::factory()->create();
    $alice = User::factory()->create(['name' => 'Alice']);
    $bob = User::factory()->create(['name' => 'Bob']);
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '@Alice and @Bob',
    ]);

    $comment->mentions()->attach($alice->id, ['commenter_type' => $alice->getMorphClass()]);
    $comment->mentions()->attach($bob->id, ['commenter_type' => $bob->getMorphClass()]);

    $rendered = $comment->renderBodyWithMentions();

    expect($rendered)->toContain('@Alice</span>');
    expect($rendered)->toContain('@Bob</span>');
    expect($rendered)->toContain('comment-mention');
});

it('does not style non-mentioned @text', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '@ghost is not here',
    ]);

    $rendered = $comment->renderBodyWithMentions();

    expect($rendered)->not->toContain('comment-mention');
});

it('renders comment-mention class in Livewire component', function () {
    $user = User::factory()->create();
    $alice = User::factory()->create(['name' => 'Alice']);
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => 'Hello @Alice',
    ]);

    $comment->mentions()->attach($alice->id, ['commenter_type' => $alice->getMorphClass()]);

    $this->actingAs($user);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->assertSeeHtml('comment-mention');
});
