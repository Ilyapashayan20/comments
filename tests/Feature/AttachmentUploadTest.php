<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Relaticle\Comments\CommentsConfig;
use Relaticle\Comments\Livewire\CommentItem;
use Relaticle\Comments\Livewire\Comments;
use Relaticle\Comments\Models\Attachment;
use Relaticle\Comments\Models\Comment;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('creates comment with file attachment via Livewire component', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    Livewire::test(Comments::class, ['model' => $post])
        ->set('newComment', '<p>Comment with attachment</p>')
        ->set('attachments', [$file])
        ->call('addComment')
        ->assertSet('newComment', '')
        ->assertSet('attachments', []);

    expect(Comment::count())->toBe(1);
    expect(Attachment::count())->toBe(1);
});

it('stores attachment with correct metadata', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    $file = UploadedFile::fake()->image('vacation.jpg', 200, 200)->size(512);

    Livewire::test(Comments::class, ['model' => $post])
        ->set('newComment', '<p>Vacation photos</p>')
        ->set('attachments', [$file])
        ->call('addComment');

    $attachment = Attachment::first();
    $comment = Comment::first();

    expect($attachment->original_name)->toBe('vacation.jpg')
        ->and($attachment->mime_type)->toBe('image/jpeg')
        ->and($attachment->size)->toBeGreaterThan(0)
        ->and($attachment->disk)->toBe('public')
        ->and($attachment->comment_id)->toBe($comment->id)
        ->and($attachment->file_path)->toStartWith("comments/attachments/{$comment->id}/");
});

it('stores file on configured disk at comments/attachments/{comment_id}/ path', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    $file = UploadedFile::fake()->image('test.png', 50, 50);

    Livewire::test(Comments::class, ['model' => $post])
        ->set('newComment', '<p>File path test</p>')
        ->set('attachments', [$file])
        ->call('addComment');

    $attachment = Attachment::first();

    Storage::disk('public')->assertExists($attachment->file_path);
    expect($attachment->file_path)->toContain("comments/attachments/{$attachment->comment_id}/");
});

it('displays image attachment thumbnail in comment item view', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Image comment</p>',
    ]);

    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);
    $path = $file->store("comments/attachments/{$comment->id}", 'public');

    Attachment::create([
        'comment_id' => $comment->id,
        'file_path' => $path,
        'original_name' => 'photo.jpg',
        'mime_type' => 'image/jpeg',
        'size' => $file->getSize(),
        'disk' => 'public',
    ]);

    $comment->load('attachments');

    $this->actingAs($user);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->assertSeeHtml('max-h-[200px]')
        ->assertSeeHtml('photo.jpg');
});

it('displays non-image attachment as download link', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>PDF comment</p>',
    ]);

    $file = UploadedFile::fake()->create('document.pdf', 2048, 'application/pdf');
    $path = $file->store("comments/attachments/{$comment->id}", 'public');

    Attachment::create([
        'comment_id' => $comment->id,
        'file_path' => $path,
        'original_name' => 'document.pdf',
        'mime_type' => 'application/pdf',
        'size' => $file->getSize(),
        'disk' => 'public',
    ]);

    $comment->load('attachments');

    $this->actingAs($user);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->assertSeeHtml('document.pdf')
        ->assertSeeHtml('download="document.pdf"');
});

it('rejects file exceeding max size', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    $oversizedFile = UploadedFile::fake()->create('big.pdf', CommentsConfig::getAttachmentMaxSize() + 1, 'application/pdf');

    Livewire::test(Comments::class, ['model' => $post])
        ->set('newComment', '<p>Oversized file</p>')
        ->set('attachments', [$oversizedFile])
        ->call('addComment')
        ->assertHasErrors('attachments.0');

    expect(Comment::count())->toBe(0);
    expect(Attachment::count())->toBe(0);
});

it('rejects disallowed file type', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    $exeFile = UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload');

    Livewire::test(Comments::class, ['model' => $post])
        ->set('newComment', '<p>Malicious file</p>')
        ->set('attachments', [$exeFile])
        ->call('addComment')
        ->assertHasErrors('attachments.0');

    expect(Comment::count())->toBe(0);
    expect(Attachment::count())->toBe(0);
});

it('accepts allowed file types', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    $imageFile = UploadedFile::fake()->image('photo.jpg', 100, 100);

    Livewire::test(Comments::class, ['model' => $post])
        ->set('newComment', '<p>Valid file</p>')
        ->set('attachments', [$imageFile])
        ->call('addComment')
        ->assertHasNoErrors('attachments.0');

    expect(Comment::count())->toBe(1);
    expect(Attachment::count())->toBe(1);
});

it('hides upload UI when attachments disabled', function () {
    config(['comments.attachments.enabled' => false]);

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    Livewire::test(Comments::class, ['model' => $post])
        ->assertDontSeeHtml('Attach files');
});

it('shows upload UI when attachments enabled', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    Livewire::test(Comments::class, ['model' => $post])
        ->assertSeeHtml('Attach files');
});

it('creates comment with multiple file attachments', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    $file1 = UploadedFile::fake()->image('photo1.jpg', 100, 100);
    $file2 = UploadedFile::fake()->create('notes.pdf', 512, 'application/pdf');

    Livewire::test(Comments::class, ['model' => $post])
        ->set('newComment', '<p>Multiple files</p>')
        ->set('attachments', [$file1, $file2])
        ->call('addComment');

    expect(Comment::count())->toBe(1);
    expect(Attachment::count())->toBe(2);

    $attachments = Attachment::all();
    expect($attachments->pluck('original_name')->toArray())
        ->toContain('photo1.jpg')
        ->toContain('notes.pdf');
});

it('creates reply with file attachment via CommentItem component', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Parent comment</p>',
    ]);

    $this->actingAs($user);

    $file = UploadedFile::fake()->image('reply-photo.png', 80, 80);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('startReply')
        ->set('replyBody', '<p>Reply with attachment</p>')
        ->set('replyAttachments', [$file])
        ->call('addReply')
        ->assertSet('isReplying', false)
        ->assertSet('replyBody', '')
        ->assertSet('replyAttachments', []);

    $reply = Comment::where('parent_id', $comment->id)->first();

    expect($reply)->not->toBeNull();
    expect($reply->attachments)->toHaveCount(1);
    expect($reply->attachments->first()->original_name)->toBe('reply-photo.png');
});

it('removes attachment from pending list before submission', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    $file1 = UploadedFile::fake()->image('photo1.jpg', 50, 50);
    $file2 = UploadedFile::fake()->image('photo2.jpg', 50, 50);

    $component = Livewire::test(Comments::class, ['model' => $post])
        ->set('attachments', [$file1, $file2]);

    expect($component->get('attachments'))->toHaveCount(2);

    $component->call('removeAttachment', 0);

    expect($component->get('attachments'))->toHaveCount(1);
});
