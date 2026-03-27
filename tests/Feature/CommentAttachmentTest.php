<?php

use Relaticle\Comments\CommentsConfig;
use Relaticle\Comments\Models\Attachment;
use Relaticle\Comments\Models\Comment;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('creates a comment attachment with all metadata fields', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Test comment</p>',
    ]);

    $attachment = Attachment::create([
        'comment_id' => $comment->id,
        'file_path' => 'comments/attachments/1/photo.jpg',
        'original_name' => 'photo.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 2048,
        'disk' => 'public',
    ]);

    expect($attachment)->toBeInstanceOf(Attachment::class)
        ->and($attachment->file_path)->toBe('comments/attachments/1/photo.jpg')
        ->and($attachment->original_name)->toBe('photo.jpg')
        ->and($attachment->mime_type)->toBe('image/jpeg')
        ->and($attachment->size)->toBe(2048)
        ->and($attachment->disk)->toBe('public');
});

it('belongs to a comment via comment() relationship', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Test</p>',
    ]);

    $attachment = Attachment::create([
        'comment_id' => $comment->id,
        'file_path' => 'comments/attachments/1/test.png',
        'original_name' => 'test.png',
        'mime_type' => 'image/png',
        'size' => 1024,
        'disk' => 'public',
    ]);

    expect($attachment->comment)->toBeInstanceOf(Comment::class)
        ->and($attachment->comment->id)->toBe($comment->id);
});

it('has attachments() hasMany relationship on Comment', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Test</p>',
    ]);

    Attachment::create([
        'comment_id' => $comment->id,
        'file_path' => 'comments/attachments/1/file1.png',
        'original_name' => 'file1.png',
        'mime_type' => 'image/png',
        'size' => 2048,
        'disk' => 'public',
    ]);

    Attachment::create([
        'comment_id' => $comment->id,
        'file_path' => 'comments/attachments/1/file2.pdf',
        'original_name' => 'file2.pdf',
        'mime_type' => 'application/pdf',
        'size' => 5120,
        'disk' => 'public',
    ]);

    expect($comment->attachments)->toHaveCount(2)
        ->and($comment->attachments->first())->toBeInstanceOf(Attachment::class);
});

it('cascade deletes attachments when comment is force deleted', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Test</p>',
    ]);

    Attachment::create([
        'comment_id' => $comment->id,
        'file_path' => 'comments/attachments/1/photo.jpg',
        'original_name' => 'photo.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1024,
        'disk' => 'public',
    ]);

    expect(Attachment::where('comment_id', $comment->id)->count())->toBe(1);

    $comment->forceDelete();

    expect(Attachment::where('comment_id', $comment->id)->count())->toBe(0);
});

it('correctly identifies image and non-image mime types via isImage()', function (string $mimeType, bool $expected) {
    $attachment = new Attachment(['mime_type' => $mimeType]);

    expect($attachment->isImage())->toBe($expected);
})->with([
    'image/jpeg is image' => ['image/jpeg', true],
    'image/png is image' => ['image/png', true],
    'image/gif is image' => ['image/gif', true],
    'image/webp is image' => ['image/webp', true],
    'application/pdf is not image' => ['application/pdf', false],
    'text/plain is not image' => ['text/plain', false],
]);

it('formats bytes into human-readable size via formattedSize()', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Test</p>',
    ]);

    $attachment = Attachment::create([
        'comment_id' => $comment->id,
        'file_path' => 'comments/attachments/1/file.pdf',
        'original_name' => 'file.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1024,
        'disk' => 'public',
    ]);

    expect($attachment->formattedSize())->toContain('KB');
});

it('returns default attachment disk as public', function () {
    expect(CommentsConfig::getAttachmentDisk())->toBe('public');
});

it('returns default attachment max size as 10240', function () {
    expect(CommentsConfig::getAttachmentMaxSize())->toBe(10240);
});

it('returns default allowed attachment types', function () {
    $allowedTypes = CommentsConfig::getAttachmentAllowedTypes();

    expect($allowedTypes)->toBeArray()
        ->toContain('image/jpeg')
        ->toContain('image/png')
        ->toContain('application/pdf');
});

it('respects custom config overrides for attachment settings', function () {
    config(['comments.attachments.disk' => 's3']);
    config(['comments.attachments.max_size' => 5120]);
    config(['comments.attachments.allowed_types' => ['image/png']]);

    expect(CommentsConfig::getAttachmentDisk())->toBe('s3')
        ->and(CommentsConfig::getAttachmentMaxSize())->toBe(5120)
        ->and(CommentsConfig::getAttachmentAllowedTypes())->toBe(['image/png']);
});

it('reports attachments as enabled by default', function () {
    expect(CommentsConfig::areAttachmentsEnabled())->toBeTrue();
});

it('respects disabled attachments config', function () {
    config(['comments.attachments.enabled' => false]);

    expect(CommentsConfig::areAttachmentsEnabled())->toBeFalse();
});
