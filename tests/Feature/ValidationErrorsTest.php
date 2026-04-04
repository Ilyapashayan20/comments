<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Relaticle\Comments\CommentsConfig;
use Relaticle\Comments\Livewire\CommentItem;
use Relaticle\Comments\Models\Comment;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('shows validation error when saving an edit with an empty body', function () {
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
        ->set('editData.body', '')
        ->call('saveEdit')
        ->assertHasErrors('editData.body');

    $comment->refresh();
    expect($comment->body)->toBe('<p>Original body</p>');
    expect($comment->isEdited())->toBeFalse();
});

it('does not set edited_at when edit body fails validation', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Unchanged</p>',
    ]);

    $this->actingAs($user);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('startEdit')
        ->set('editData.body', '')
        ->call('saveEdit');

    $comment->refresh();
    expect($comment->edited_at)->toBeNull();
});

it('shows validation error when adding a reply with an empty body', function () {
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
        ->set('replyData.body', '')
        ->call('addReply')
        ->assertHasErrors('replyData.body');

    expect(Comment::where('parent_id', $comment->id)->count())->toBe(0);
});

it('shows validation error for oversized reply attachment', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
    ]);

    $this->actingAs($user);

    $oversized = UploadedFile::fake()->create(
        'huge.pdf',
        CommentsConfig::getAttachmentMaxSize() + 1,
        'application/pdf'
    );

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('startReply')
        ->set('replyData.body', '<p>Reply with big file</p>')
        ->set('replyAttachments', [$oversized])
        ->call('addReply')
        ->assertHasErrors('replyAttachments.0');

    expect(Comment::where('parent_id', $comment->id)->count())->toBe(0);
});

it('shows validation error for disallowed reply attachment type', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
    ]);

    $this->actingAs($user);

    $exeFile = UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload');

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('startReply')
        ->set('replyData.body', '<p>Reply with bad file</p>')
        ->set('replyAttachments', [$exeFile])
        ->call('addReply')
        ->assertHasErrors('replyAttachments.0');

    expect(Comment::where('parent_id', $comment->id)->count())->toBe(0);
});
