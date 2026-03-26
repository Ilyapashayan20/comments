<?php

use Carbon\Carbon;
use Relaticle\Comments\Comment;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('can be created with factory', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
    ]);

    expect($comment)->toBeInstanceOf(Comment::class);
    expect($comment->body)->toBeString();
    expect($comment->commentable_id)->toBe($post->id);
    expect($comment->user_id)->toBe($user->id);
});

it('belongs to a commentable model via morphTo', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
    ]);

    expect($comment->commentable)->toBeInstanceOf(Post::class);
    expect($comment->commentable->id)->toBe($post->id);
});

it('belongs to a user via morphTo', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
    ]);

    expect($comment->user)->toBeInstanceOf(User::class);
    expect($comment->user->id)->toBe($user->id);
});

it('supports threading with parent and replies', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $parent = Comment::factory()->create([
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
    ]);

    $reply = Comment::factory()->withParent($parent)->create([
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
    ]);

    expect($reply->parent->id)->toBe($parent->id);
    expect($parent->replies)->toHaveCount(1);
    expect($parent->replies->first()->id)->toBe($reply->id);
});

it('identifies top-level vs reply comments', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $topLevel = Comment::factory()->create([
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
    ]);

    $reply = Comment::factory()->withParent($topLevel)->create([
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
    ]);

    expect($topLevel->isTopLevel())->toBeTrue();
    expect($topLevel->isReply())->toBeFalse();
    expect($reply->isReply())->toBeTrue();
    expect($reply->isTopLevel())->toBeFalse();
});

it('calculates depth correctly', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    $attrs = [
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
    ];

    $level0 = Comment::factory()->create($attrs);
    $level1 = Comment::factory()->withParent($level0)->create($attrs);
    $level2 = Comment::factory()->withParent($level1)->create($attrs);

    expect($level0->depth())->toBe(0);
    expect($level1->depth())->toBe(1);
    expect($level2->depth())->toBe(2);
});

it('checks canReply based on max depth', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    $attrs = [
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
    ];

    $level0 = Comment::factory()->create($attrs);
    $level1 = Comment::factory()->withParent($level0)->create($attrs);
    $level2 = Comment::factory()->withParent($level1)->create($attrs);

    expect($level0->canReply())->toBeTrue();
    expect($level1->canReply())->toBeTrue();
    expect($level2->canReply())->toBeFalse();
});

it('supports soft deletes', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
    ]);

    $comment->delete();

    expect(Comment::find($comment->id))->toBeNull();
    expect(Comment::withTrashed()->find($comment->id))->not->toBeNull();
    expect(Comment::withTrashed()->find($comment->id)->trashed())->toBeTrue();
});

it('tracks edited state', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
    ]);

    expect($comment->isEdited())->toBeFalse();

    $edited = Comment::factory()->edited()->create([
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
    ]);

    expect($edited->isEdited())->toBeTrue();
    expect($edited->edited_at)->toBeInstanceOf(Carbon::class);
});

it('detects when it has replies', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    $attrs = [
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
    ];

    $parent = Comment::factory()->create($attrs);
    expect($parent->hasReplies())->toBeFalse();

    Comment::factory()->withParent($parent)->create($attrs);
    expect($parent->hasReplies())->toBeTrue();
});
