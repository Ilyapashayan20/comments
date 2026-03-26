<?php

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Relaticle\Comments\Comment;
use Relaticle\Comments\Events\CommentCreated;
use Relaticle\Comments\Events\CommentDeleted;
use Relaticle\Comments\Events\CommentReacted;
use Relaticle\Comments\Events\CommentUpdated;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('CommentCreated event implements ShouldBroadcast', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    $event = new CommentCreated($comment);

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);
});

it('CommentUpdated event implements ShouldBroadcast', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    $event = new CommentUpdated($comment);

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);
});

it('CommentDeleted event implements ShouldBroadcast', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    $event = new CommentDeleted($comment);

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);
});

it('CommentReacted event implements ShouldBroadcast', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    $event = new CommentReacted($comment, $user, 'thumbs_up', 'added');

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);
});

it('broadcastOn returns PrivateChannel with correct channel name', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    $event = new CommentCreated($comment);
    $channels = $event->broadcastOn();

    expect($channels)->toBeArray()
        ->and($channels[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)->toBe("private-comments.{$post->getMorphClass()}.{$post->id}");
});

it('broadcastWhen returns false when broadcasting is disabled', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    $event = new CommentCreated($comment);

    expect($event->broadcastWhen())->toBeFalse();
});

it('broadcastWhen returns true when broadcasting is enabled', function () {
    config()->set('comments.broadcasting.enabled', true);

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    $event = new CommentCreated($comment);

    expect($event->broadcastWhen())->toBeTrue();
});

it('broadcastWith returns array with comment_id for CommentCreated', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    $event = new CommentCreated($comment);
    $data = $event->broadcastWith();

    expect($data)->toBeArray()
        ->toHaveKey('comment_id', $comment->id)
        ->toHaveKey('commentable_type', $post->getMorphClass())
        ->toHaveKey('commentable_id', $post->id);
});

it('broadcastWith returns array with comment_id, reaction, and action for CommentReacted', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    $event = new CommentReacted($comment, $user, 'thumbs_up', 'added');
    $data = $event->broadcastWith();

    expect($data)->toBeArray()
        ->toHaveKey('comment_id', $comment->id)
        ->toHaveKey('reaction', 'thumbs_up')
        ->toHaveKey('action', 'added');
});

it('uses custom channel prefix from config in broadcastOn', function () {
    config()->set('comments.broadcasting.channel_prefix', 'custom-prefix');

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    $event = new CommentCreated($comment);
    $channels = $event->broadcastOn();

    expect($channels[0]->name)->toBe("private-custom-prefix.{$post->getMorphClass()}.{$post->id}");
});
