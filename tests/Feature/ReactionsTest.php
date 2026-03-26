<?php

use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Relaticle\Comments\Comment;
use Relaticle\Comments\CommentReaction;
use Relaticle\Comments\Config;
use Relaticle\Comments\Events\CommentReacted;
use Relaticle\Comments\Livewire\Reactions;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('adds a reaction when user clicks an emoji', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    $this->actingAs($user);

    Livewire::test(Reactions::class, ['comment' => $comment])
        ->call('toggleReaction', 'thumbs_up');

    expect(CommentReaction::where([
        'comment_id' => $comment->id,
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'reaction' => 'thumbs_up',
    ])->exists())->toBeTrue();
});

it('removes a reaction when toggling same emoji', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    CommentReaction::create([
        'comment_id' => $comment->id,
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'reaction' => 'thumbs_up',
    ]);

    $this->actingAs($user);

    Livewire::test(Reactions::class, ['comment' => $comment])
        ->call('toggleReaction', 'thumbs_up');

    expect(CommentReaction::where([
        'comment_id' => $comment->id,
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'reaction' => 'thumbs_up',
    ])->exists())->toBeFalse();
});

it('fires CommentReacted event with added action', function () {
    Event::fake([CommentReacted::class]);

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    $this->actingAs($user);

    Livewire::test(Reactions::class, ['comment' => $comment])
        ->call('toggleReaction', 'thumbs_up');

    Event::assertDispatched(CommentReacted::class, function (CommentReacted $event) use ($comment, $user) {
        return $event->comment->id === $comment->id
            && $event->user->getKey() === $user->getKey()
            && $event->reaction === 'thumbs_up'
            && $event->action === 'added';
    });
});

it('fires CommentReacted event with removed action', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    CommentReaction::create([
        'comment_id' => $comment->id,
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'reaction' => 'heart',
    ]);

    Event::fake([CommentReacted::class]);

    $this->actingAs($user);

    Livewire::test(Reactions::class, ['comment' => $comment])
        ->call('toggleReaction', 'heart');

    Event::assertDispatched(CommentReacted::class, function (CommentReacted $event) use ($comment, $user) {
        return $event->comment->id === $comment->id
            && $event->user->getKey() === $user->getKey()
            && $event->reaction === 'heart'
            && $event->action === 'removed';
    });
});

it('returns correct reaction summary with counts', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user1->getKey(),
        'user_type' => $user1->getMorphClass(),
    ]);

    CommentReaction::create([
        'comment_id' => $comment->id,
        'user_id' => $user1->getKey(),
        'user_type' => $user1->getMorphClass(),
        'reaction' => 'thumbs_up',
    ]);

    CommentReaction::create([
        'comment_id' => $comment->id,
        'user_id' => $user2->getKey(),
        'user_type' => $user2->getMorphClass(),
        'reaction' => 'thumbs_up',
    ]);

    CommentReaction::create([
        'comment_id' => $comment->id,
        'user_id' => $user3->getKey(),
        'user_type' => $user3->getMorphClass(),
        'reaction' => 'thumbs_up',
    ]);

    CommentReaction::create([
        'comment_id' => $comment->id,
        'user_id' => $user1->getKey(),
        'user_type' => $user1->getMorphClass(),
        'reaction' => 'heart',
    ]);

    $this->actingAs($user1);

    $component = Livewire::test(Reactions::class, ['comment' => $comment]);
    $summary = $component->instance()->reactionSummary;

    expect($summary)->toHaveCount(2);

    $thumbsUp = collect($summary)->firstWhere('reaction', 'thumbs_up');
    expect($thumbsUp['count'])->toBe(3);
    expect($thumbsUp['names'])->toHaveCount(3);

    $heart = collect($summary)->firstWhere('reaction', 'heart');
    expect($heart['count'])->toBe(1);
});

it('requires authentication to react', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    Livewire::test(Reactions::class, ['comment' => $comment])
        ->call('toggleReaction', 'thumbs_up');

    expect(CommentReaction::count())->toBe(0);
});

it('allows multiple reaction types from same user', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Reactions::class, ['comment' => $comment]);

    $component->call('toggleReaction', 'thumbs_up');
    $component->call('toggleReaction', 'heart');

    expect(CommentReaction::where([
        'comment_id' => $comment->id,
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'reaction' => 'thumbs_up',
    ])->exists())->toBeTrue();

    expect(CommentReaction::where([
        'comment_id' => $comment->id,
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'reaction' => 'heart',
    ])->exists())->toBeTrue();
});

it('allows same reaction from multiple users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user1->getKey(),
        'user_type' => $user1->getMorphClass(),
    ]);

    $this->actingAs($user1);
    Livewire::test(Reactions::class, ['comment' => $comment])
        ->call('toggleReaction', 'thumbs_up');

    $this->actingAs($user2);
    Livewire::test(Reactions::class, ['comment' => $comment])
        ->call('toggleReaction', 'thumbs_up');

    expect(CommentReaction::where([
        'comment_id' => $comment->id,
        'reaction' => 'thumbs_up',
    ])->count())->toBe(2);
});

it('rejects invalid reaction keys', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    $this->actingAs($user);

    Livewire::test(Reactions::class, ['comment' => $comment])
        ->call('toggleReaction', 'invalid_emoji');

    expect(CommentReaction::count())->toBe(0);
});

it('marks reacted_by_user correctly in summary', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $userA->getKey(),
        'user_type' => $userA->getMorphClass(),
    ]);

    CommentReaction::create([
        'comment_id' => $comment->id,
        'user_id' => $userA->getKey(),
        'user_type' => $userA->getMorphClass(),
        'reaction' => 'thumbs_up',
    ]);

    $this->actingAs($userA);
    $summaryA = Livewire::test(Reactions::class, ['comment' => $comment])
        ->instance()->reactionSummary;

    $thumbsUpA = collect($summaryA)->firstWhere('reaction', 'thumbs_up');
    expect($thumbsUpA['reacted_by_user'])->toBeTrue();

    $this->actingAs($userB);
    $summaryB = Livewire::test(Reactions::class, ['comment' => $comment])
        ->instance()->reactionSummary;

    $thumbsUpB = collect($summaryB)->firstWhere('reaction', 'thumbs_up');
    expect($thumbsUpB['reacted_by_user'])->toBeFalse();
});

it('returns configured emoji set from config', function () {
    $emojiSet = Config::getReactionEmojiSet();

    expect($emojiSet)->toBeArray();
    expect($emojiSet)->toHaveKey('thumbs_up');
    expect($emojiSet)->toHaveKey('heart');
    expect($emojiSet)->toHaveKey('celebrate');
    expect($emojiSet)->toHaveKey('laugh');
    expect($emojiSet)->toHaveKey('thinking');
    expect($emojiSet)->toHaveKey('sad');
});

it('returns allowed reaction keys from config', function () {
    $allowed = Config::getAllowedReactions();

    expect($allowed)->toBeArray();
    expect($allowed)->toContain('thumbs_up');
    expect($allowed)->toContain('heart');
    expect($allowed)->toContain('celebrate');
    expect($allowed)->toContain('laugh');
    expect($allowed)->toContain('thinking');
    expect($allowed)->toContain('sad');
});
