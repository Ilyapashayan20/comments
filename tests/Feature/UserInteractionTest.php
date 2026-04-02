<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Relaticle\Comments\Livewire\CommentItem;
use Relaticle\Comments\Livewire\Comments;
use Relaticle\Comments\Models\Comment;
use Relaticle\Comments\Models\Subscription;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('pre-fills edit form with the current comment body', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Current content</p>',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('startEdit');

    // RichEditor stores content as ProseMirror JSON internally
    $body = $component->get('editData')['body'];
    expect(json_encode($body))->toContain('Current content');
});

it('cancelling edit leaves comment body unchanged in database', function () {
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
        ->set('editData.body', '<p>Partial edit</p>')
        ->call('cancelEdit')
        ->assertSet('isEditing', false);

    $comment->refresh();
    expect($comment->body)->toBe('<p>Original</p>');
    expect($comment->edited_at)->toBeNull();
});

it('cancelling reply clears reply data and attachments', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
    ]);

    $this->actingAs($user);

    $file = UploadedFile::fake()->image('draft.jpg', 50, 50);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('startReply')
        ->set('replyData.body', '<p>Draft reply</p>')
        ->set('replyAttachments', [$file])
        ->call('cancelReply')
        ->assertSet('isReplying', false)
        ->assertSet('replyAttachments', []);
});

it('resets comment form after successful submission', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(Comments::class, ['model' => $post])
        ->set('commentData.body', '<p>Hello</p>')
        ->call('addComment');

    // After reset, the typed content should no longer be in the form state
    $body = $component->get('commentData')['body'] ?? null;
    expect(json_encode($body))->not->toContain('Hello');
    expect($component->get('attachments'))->toBe([]);
});

it('forbids non-author from directly calling saveEdit', function () {
    $author = User::factory()->create();
    $other = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $author->getKey(),
        'commenter_type' => $author->getMorphClass(),
        'body' => '<p>Author wrote this</p>',
    ]);

    $this->actingAs($other);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->set('editData.body', '<p>Hijacked body</p>')
        ->call('saveEdit')
        ->assertForbidden();

    $comment->refresh();
    expect($comment->body)->toBe('<p>Author wrote this</p>');
});

it('forbids unauthenticated user from adding a reply', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
    ]);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->set('replyData.body', '<p>Guest reply</p>')
        ->call('addReply')
        ->assertForbidden();

    expect(Comment::where('parent_id', $comment->id)->count())->toBe(0);
});

it('dispatches commentDeleted browser event after deleting a comment', function () {
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
        ->call('deleteComment')
        ->assertDispatched('commentDeleted');
});

it('dispatches commentUpdated browser event after saving an edit', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Before</p>',
    ]);

    $this->actingAs($user);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('startEdit')
        ->set('editData.body', '<p>After</p>')
        ->call('saveEdit')
        ->assertDispatched('commentUpdated');
});

it('dispatches commentUpdated browser event after adding a reply', function () {
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
        ->set('replyData.body', '<p>A reply</p>')
        ->call('addReply')
        ->assertDispatched('commentUpdated');
});

it('auto-subscribes comment author when auto_subscribe is enabled', function () {
    config(['comments.subscriptions.auto_subscribe' => true]);

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    Livewire::test(Comments::class, ['model' => $post])
        ->set('commentData.body', '<p>First comment</p>')
        ->call('addComment');

    expect(Subscription::isSubscribed($post, $user))->toBeTrue();
});

it('removes pending attachment at specific index while preserving others', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    $file1 = UploadedFile::fake()->image('first.jpg', 50, 50);
    $file2 = UploadedFile::fake()->image('second.jpg', 50, 50);
    $file3 = UploadedFile::fake()->image('third.jpg', 50, 50);

    $component = Livewire::test(Comments::class, ['model' => $post])
        ->set('attachments', [$file1, $file2, $file3]);

    expect($component->get('attachments'))->toHaveCount(3);

    $component->call('removeAttachment', 1);

    expect($component->get('attachments'))->toHaveCount(2);
});

it('removes pending reply attachment at specific index', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
    ]);

    $this->actingAs($user);

    $file1 = UploadedFile::fake()->image('a.jpg', 50, 50);
    $file2 = UploadedFile::fake()->image('b.jpg', 50, 50);

    $component = Livewire::test(CommentItem::class, ['comment' => $comment])
        ->set('replyAttachments', [$file1, $file2]);

    expect($component->get('replyAttachments'))->toHaveCount(2);

    $component->call('removeReplyAttachment', 0);

    expect($component->get('replyAttachments'))->toHaveCount(1);
});

it('allCommentsCount includes replies in the total', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $attrs = [
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
    ];

    $parent = Comment::factory()->create($attrs);
    Comment::factory()->count(2)->withParent($parent)->create($attrs);

    $this->actingAs($user);

    $component = Livewire::test(Comments::class, ['model' => $post]);

    expect($component->instance()->allCommentsCount())->toBe(3);
    expect($component->instance()->totalCount())->toBe(1);
});

it('hasMore is false when loaded count exceeds total', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    config(['comments.pagination.per_page' => 10]);

    Comment::factory()->count(3)->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Comments::class, ['model' => $post]);

    expect($component->instance()->hasMore())->toBeFalse();
});

it('hasMore is true when there are more comments beyond loaded count', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    config(['comments.pagination.per_page' => 2]);

    Comment::factory()->count(5)->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Comments::class, ['model' => $post]);

    expect($component->instance()->hasMore())->toBeTrue();
});
