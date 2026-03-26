<?php

use Relaticle\Comments\Comment;
use Relaticle\Comments\CommentSubscription;
use Relaticle\Comments\Config;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('has commentable morphTo relationship', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $subscription = CommentSubscription::create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    expect($subscription->commentable)->toBeInstanceOf(Post::class)
        ->and($subscription->commentable->id)->toBe($post->id);
});

it('has user morphTo relationship', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $subscription = CommentSubscription::create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    expect($subscription->user)->toBeInstanceOf(User::class)
        ->and($subscription->user->id)->toBe($user->id);
});

it('returns database as default notification channel', function () {
    expect(Config::getNotificationChannels())->toBe(['database']);
});

it('returns custom channels when configured', function () {
    config()->set('comments.notifications.channels', ['database', 'mail']);

    expect(Config::getNotificationChannels())->toBe(['database', 'mail']);
});

it('returns true for shouldAutoSubscribe by default', function () {
    expect(Config::shouldAutoSubscribe())->toBeTrue();
});

it('returns false for shouldAutoSubscribe when configured', function () {
    config()->set('comments.subscriptions.auto_subscribe', false);

    expect(Config::shouldAutoSubscribe())->toBeFalse();
});

it('checks if user is subscribed to a commentable via isSubscribed()', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    expect(CommentSubscription::isSubscribed($post, $user))->toBeFalse();

    CommentSubscription::create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    expect(CommentSubscription::isSubscribed($post, $user))->toBeTrue();
});

it('creates subscription via subscribe() static method', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    CommentSubscription::subscribe($post, $user);

    expect(CommentSubscription::isSubscribed($post, $user))->toBeTrue();
});

it('removes subscription via unsubscribe() static method', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    CommentSubscription::subscribe($post, $user);
    CommentSubscription::unsubscribe($post, $user);

    expect(CommentSubscription::isSubscribed($post, $user))->toBeFalse();
});

it('is idempotent when subscribing twice', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    CommentSubscription::subscribe($post, $user);
    CommentSubscription::subscribe($post, $user);

    expect(CommentSubscription::where([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ])->count())->toBe(1);
});
