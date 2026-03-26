<?php

use Illuminate\Database\QueryException;
use Relaticle\Comments\Comment;
use Relaticle\Comments\CommentReaction;
use Relaticle\Comments\Events\CommentReacted;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('belongs to a comment via comment() relationship', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'body' => '<p>Test</p>',
    ]);

    $reaction = CommentReaction::create([
        'comment_id' => $comment->id,
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'reaction' => 'thumbs_up',
    ]);

    expect($reaction->comment)->toBeInstanceOf(Comment::class)
        ->and($reaction->comment->id)->toBe($comment->id);
});

it('belongs to a user via polymorphic user() relationship', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'body' => '<p>Test</p>',
    ]);

    $reaction = CommentReaction::create([
        'comment_id' => $comment->id,
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'reaction' => 'heart',
    ]);

    expect($reaction->user)->toBeInstanceOf(User::class)
        ->and($reaction->user->id)->toBe($user->id);
});

it('prevents duplicate reactions with unique constraint', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'body' => '<p>Test</p>',
    ]);

    CommentReaction::create([
        'comment_id' => $comment->id,
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'reaction' => 'thumbs_up',
    ]);

    expect(fn () => CommentReaction::create([
        'comment_id' => $comment->id,
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'reaction' => 'thumbs_up',
    ]))->toThrow(QueryException::class);
});

it('carries comment, user, reaction key, and action in CommentReacted event', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
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
