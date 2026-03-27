<?php

use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Relaticle\Comments\Events\CommentCreated;
use Relaticle\Comments\Events\CommentDeleted;
use Relaticle\Comments\Events\CommentUpdated;
use Relaticle\Comments\Livewire\CommentItem;
use Relaticle\Comments\Livewire\Comments;
use Relaticle\Comments\Models\Comment;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('fires CommentCreated event when adding a comment', function () {
    Event::fake([CommentCreated::class]);

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    Livewire::test(Comments::class, ['model' => $post])
        ->set('newComment', '<p>New comment</p>')
        ->call('addComment');

    Event::assertDispatched(CommentCreated::class, function (CommentCreated $event) use ($post) {
        return $event->comment->body === '<p>New comment</p>'
            && $event->commentable->id === $post->id;
    });
});

it('fires CommentUpdated event when editing a comment', function () {
    Event::fake([CommentUpdated::class]);

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Original</p>',
    ]);

    $this->actingAs($user);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('startEdit')
        ->set('editBody', '<p>Edited</p>')
        ->call('saveEdit');

    Event::assertDispatched(CommentUpdated::class, function (CommentUpdated $event) use ($comment) {
        return $event->comment->id === $comment->id;
    });
});

it('fires CommentDeleted event when deleting a comment', function () {
    Event::fake([CommentDeleted::class]);

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
    ]);

    $this->actingAs($user);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('deleteComment');

    Event::assertDispatched(CommentDeleted::class, function (CommentDeleted $event) use ($comment) {
        return $event->comment->id === $comment->id;
    });
});

it('fires CommentCreated event when adding a reply', function () {
    Event::fake([CommentCreated::class]);

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
    ]);

    $this->actingAs($user);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('startReply')
        ->set('replyBody', '<p>Reply text</p>')
        ->call('addReply');

    Event::assertDispatched(CommentCreated::class, function (CommentCreated $event) use ($comment) {
        return $event->comment->parent_id === $comment->id
            && $event->comment->body === '<p>Reply text</p>';
    });
});

it('carries correct comment and commentable in event payload', function () {
    Event::fake([CommentCreated::class]);

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    Livewire::test(Comments::class, ['model' => $post])
        ->set('newComment', '<p>Payload test</p>')
        ->call('addComment');

    Event::assertDispatched(CommentCreated::class, function (CommentCreated $event) use ($post, $user) {
        return $event->comment instanceof Comment
            && $event->commentable->id === $post->id
            && $event->comment->commenter_id === $user->id;
    });
});
