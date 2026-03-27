<?php

use Relaticle\Comments\Models\Comment;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('provides comments relationship on commentable model', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    Comment::factory()->count(3)->create([
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->id,
        'commenter_type' => $user->getMorphClass(),
        'commenter_id' => $user->id,
    ]);

    expect($post->comments)->toHaveCount(3);
    expect($post->comments->first())->toBeInstanceOf(Comment::class);
});

it('provides topLevelComments excluding replies', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    $attrs = [
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->id,
        'commenter_type' => $user->getMorphClass(),
        'commenter_id' => $user->id,
    ];

    $topLevel = Comment::factory()->create($attrs);
    Comment::factory()->withParent($topLevel)->create($attrs);

    expect($post->comments()->count())->toBe(2);
    expect($post->topLevelComments()->count())->toBe(1);
    expect($post->topLevelComments->first()->id)->toBe($topLevel->id);
});

it('provides comment count', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    expect($post->commentCount())->toBe(0);

    Comment::factory()->count(5)->create([
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->id,
        'commenter_type' => $user->getMorphClass(),
        'commenter_id' => $user->id,
    ]);

    expect($post->commentCount())->toBe(5);
});

it('scopes comments to the specific commentable', function () {
    $user = User::factory()->create();
    $post1 = Post::factory()->create();
    $post2 = Post::factory()->create();

    Comment::factory()->count(3)->create([
        'commentable_type' => $post1->getMorphClass(),
        'commentable_id' => $post1->id,
        'commenter_type' => $user->getMorphClass(),
        'commenter_id' => $user->id,
    ]);

    Comment::factory()->count(2)->create([
        'commentable_type' => $post2->getMorphClass(),
        'commentable_id' => $post2->id,
        'commenter_type' => $user->getMorphClass(),
        'commenter_id' => $user->id,
    ]);

    expect($post1->commentCount())->toBe(3);
    expect($post2->commentCount())->toBe(2);
});
