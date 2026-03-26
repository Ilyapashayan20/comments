<?php

use Livewire\Livewire;
use Relaticle\Comments\CommentSubscription;
use Relaticle\Comments\Livewire\Comments;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('subscribes user when toggling from unsubscribed state', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    expect(CommentSubscription::isSubscribed($post, $user))->toBeFalse();

    Livewire::test(Comments::class, ['model' => $post])
        ->call('toggleSubscription');

    expect(CommentSubscription::isSubscribed($post, $user))->toBeTrue();
});

it('unsubscribes user when toggling from subscribed state', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    CommentSubscription::subscribe($post, $user);

    $this->actingAs($user);

    expect(CommentSubscription::isSubscribed($post, $user))->toBeTrue();

    Livewire::test(Comments::class, ['model' => $post])
        ->call('toggleSubscription');

    expect(CommentSubscription::isSubscribed($post, $user))->toBeFalse();
});

it('returns true for isSubscribed computed when user is subscribed', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    CommentSubscription::subscribe($post, $user);

    $this->actingAs($user);

    $component = Livewire::test(Comments::class, ['model' => $post]);

    expect($component->instance()->isSubscribed())->toBeTrue();
});

it('returns false for isSubscribed computed when user is not subscribed', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(Comments::class, ['model' => $post]);

    expect($component->instance()->isSubscribed())->toBeFalse();
});

it('renders Subscribed text for subscribed user', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    CommentSubscription::subscribe($post, $user);

    $this->actingAs($user);

    Livewire::test(Comments::class, ['model' => $post])
        ->assertSee('Subscribed');
});

it('renders Subscribe text for unsubscribed user', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    Livewire::test(Comments::class, ['model' => $post])
        ->assertSee('Subscribe');
});
