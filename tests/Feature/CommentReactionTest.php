<?php

use Illuminate\Database\QueryException;
use Relaticle\Comments\Events\CommentReacted;
use Relaticle\Comments\Models\Comment;
use Relaticle\Comments\Models\Reaction;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('belongs to a comment via comment() relationship', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Test</p>',
    ]);

    $reaction = Reaction::create([
        'comment_id' => $comment->id,
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'reaction' => 'thumbs_up',
    ]);

    expect($reaction->comment)->toBeInstanceOf(Comment::class)
        ->and($reaction->comment->id)->toBe($comment->id);
});

it('belongs to a commenter via polymorphic commenter() relationship', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Test</p>',
    ]);

    $reaction = Reaction::create([
        'comment_id' => $comment->id,
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'reaction' => 'heart',
    ]);

    expect($reaction->commenter)->toBeInstanceOf(User::class)
        ->and($reaction->commenter->id)->toBe($user->id);
});

it('prevents duplicate reactions with unique constraint', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Test</p>',
    ]);

    Reaction::create([
        'comment_id' => $comment->id,
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'reaction' => 'thumbs_up',
    ]);

    expect(fn () => Reaction::create([
        'comment_id' => $comment->id,
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'reaction' => 'thumbs_up',
    ]))->toThrow(QueryException::class);
});

it('carries comment, user, reaction key, and action in CommentReacted event', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Test</p>',
    ]);

    $event = new CommentReacted(
        comment: $comment,
        user: $user,
        reaction: 'heart',
        action: 'added',
    );

    expect($event->comment)->toBeInstanceOf(Comment::class)
        ->and($event->comment->id)->toBe($comment->id)
        ->and($event->user)->toBeInstanceOf(User::class)
        ->and($event->user->id)->toBe($user->id)
        ->and($event->reaction)->toBe('heart')
        ->and($event->action)->toBe('added');
});
