<?php

use Livewire\Livewire;
use Relaticle\Comments\Livewire\CommentItem;
use Relaticle\Comments\Models\Comment;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('allows author to start and save edit on their comment', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Original body</p>',
    ]);

    $this->actingAs($user);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('startEdit')
        ->assertSet('isEditing', true)
        ->assertSet('editBody', '<p>Original body</p>')
        ->set('editBody', '<p>Updated body</p>')
        ->call('saveEdit')
        ->assertSet('isEditing', false)
        ->assertSet('editBody', '');

    $comment->refresh();

    expect($comment->body)->toBe('<p>Updated body</p>');
    expect($comment->isEdited())->toBeTrue();
});

it('marks edited comment with edited indicator', function () {
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

    expect($comment->isEdited())->toBeFalse();

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('startEdit')
        ->set('editBody', '<p>Changed</p>')
        ->call('saveEdit');

    $comment->refresh();

    expect($comment->isEdited())->toBeTrue();
    expect($comment->edited_at)->not->toBeNull();
});

it('prevents non-author from editing a comment', function () {
    $author = User::factory()->create();
    $otherUser = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $author->getKey(),
        'commenter_type' => $author->getMorphClass(),
        'body' => '<p>Author comment</p>',
    ]);

    $this->actingAs($otherUser);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('startEdit')
        ->assertForbidden();
});

it('allows author to delete their own comment', function () {
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

    expect(Comment::find($comment->id))->toBeNull();
    expect(Comment::withTrashed()->find($comment->id)->trashed())->toBeTrue();
});

it('preserves replies when parent comment is deleted', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    $attrs = [
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
    ];

    $parent = Comment::factory()->create($attrs);
    $reply = Comment::factory()->withParent($parent)->create($attrs);

    $this->actingAs($user);

    Livewire::test(CommentItem::class, ['comment' => $parent])
        ->call('deleteComment');

    expect(Comment::withTrashed()->find($parent->id)->trashed())->toBeTrue();
    expect(Comment::find($reply->id))->not->toBeNull();
    expect(Comment::find($reply->id)->trashed())->toBeFalse();
});

it('prevents non-author from deleting a comment', function () {
    $author = User::factory()->create();
    $otherUser = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $author->getKey(),
        'commenter_type' => $author->getMorphClass(),
    ]);

    $this->actingAs($otherUser);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('deleteComment')
        ->assertForbidden();
});

it('allows user to reply to a comment', function () {
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
        ->assertSet('isReplying', true)
        ->set('replyBody', '<p>My reply</p>')
        ->call('addReply')
        ->assertSet('isReplying', false)
        ->assertSet('replyBody', '');

    $reply = Comment::where('parent_id', $comment->id)->first();

    expect($reply)->not->toBeNull();
    expect($reply->body)->toBe('<p>My reply</p>');
    expect($reply->commenter_id)->toBe($user->id);
    expect($reply->commentable_id)->toBe($post->id);
});

it('respects max depth for replies', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    $attrs = [
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
    ];

    config(['comments.threading.max_depth' => 1]);

    $level0 = Comment::factory()->create($attrs);
    $level1 = Comment::factory()->withParent($level0)->create($attrs);

    $this->actingAs($user);

    Livewire::test(CommentItem::class, ['comment' => $level1])
        ->call('startReply')
        ->assertSet('isReplying', false);
});

it('resets state when cancelling edit', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Some body</p>',
    ]);

    $this->actingAs($user);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('startEdit')
        ->assertSet('isEditing', true)
        ->call('cancelEdit')
        ->assertSet('isEditing', false)
        ->assertSet('editBody', '');
});

it('resets state when cancelling reply', function () {
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
        ->assertSet('isReplying', true)
        ->set('replyBody', '<p>Draft reply</p>')
        ->call('cancelReply')
        ->assertSet('isReplying', false)
        ->assertSet('replyBody', '');
});

it('loads all replies within a thread eagerly', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    $attrs = [
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
    ];

    $parent = Comment::factory()->create($attrs);
    Comment::factory()->count(3)->withParent($parent)->create($attrs);

    $parentWithReplies = Comment::with('replies.commenter')->find($parent->id);

    $this->actingAs($user);

    $component = Livewire::test(CommentItem::class, ['comment' => $parentWithReplies]);

    expect($component->instance()->comment->replies)->toHaveCount(3);
});
