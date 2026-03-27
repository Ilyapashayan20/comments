<?php

use Relaticle\Comments\CommentsConfig;
use Relaticle\Comments\Models\Subscription;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('has commentable morphTo relationship', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $subscription = Subscription::create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
    ]);

    expect($subscription->commentable)->toBeInstanceOf(Post::class)
        ->and($subscription->commentable->id)->toBe($post->id);
});

it('has commenter morphTo relationship', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $subscription = Subscription::create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
    ]);

    expect($subscription->commenter)->toBeInstanceOf(User::class)
        ->and($subscription->commenter->id)->toBe($user->id);
});

it('returns database as default notification channel', function () {
    expect(CommentsConfig::getNotificationChannels())->toBe(['database']);
});

it('returns custom channels when configured', function () {
    config()->set('comments.notifications.channels', ['database', 'mail']);

    expect(CommentsConfig::getNotificationChannels())->toBe(['database', 'mail']);
});

it('returns true for shouldAutoSubscribe by default', function () {
    expect(CommentsConfig::shouldAutoSubscribe())->toBeTrue();
});

it('returns false for shouldAutoSubscribe when configured', function () {
    config()->set('comments.subscriptions.auto_subscribe', false);

    expect(CommentsConfig::shouldAutoSubscribe())->toBeFalse();
});

it('checks if user is subscribed to a commentable via isSubscribed()', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    expect(Subscription::isSubscribed($post, $user))->toBeFalse();

    Subscription::create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
    ]);

    expect(Subscription::isSubscribed($post, $user))->toBeTrue();
});

it('creates subscription via subscribe() static method', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    Subscription::subscribe($post, $user);

    expect(Subscription::isSubscribed($post, $user))->toBeTrue();
});

it('removes subscription via unsubscribe() static method', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    Subscription::subscribe($post, $user);
    Subscription::unsubscribe($post, $user);

    expect(Subscription::isSubscribed($post, $user))->toBeFalse();
});

it('is idempotent when subscribing twice', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    Subscription::subscribe($post, $user);
    Subscription::subscribe($post, $user);

    expect(Subscription::where([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
    ])->count())->toBe(1);
});
