<?php

use Livewire\Livewire;
use Relaticle\Comments\Livewire\Comments;
use Relaticle\Comments\Models\Comment;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('strips script tags from comment body on create', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Hello</p><script>alert(1)</script>',
    ]);

    expect($comment->body)->not->toContain('<script>');
    expect($comment->body)->toContain('<p>Hello</p>');
});

it('strips event handler attributes from comment body', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<img onerror="alert(1)" src="x">',
    ]);

    expect($comment->body)->not->toContain('onerror');
    expect($comment->body)->toContain('src="x"');
});

it('strips style tags from comment body', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Hi</p><style>body{display:none}</style>',
    ]);

    expect($comment->body)->not->toContain('<style>');
    expect($comment->body)->toContain('<p>Hi</p>');
});

it('strips iframe tags from comment body', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Hi</p><iframe src="evil.com"></iframe>',
    ]);

    expect($comment->body)->not->toContain('<iframe');
    expect($comment->body)->toContain('<p>Hi</p>');
});

it('preserves safe HTML formatting through sanitization', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $safeHtml = '<p>Hello <strong>bold</strong> and <em>italic</em> text</p>'
        .'<a href="https://example.com">link</a>'
        .'<ul><li>item one</li><li>item two</li></ul>'
        .'<pre><code>echo "hello";</code></pre>'
        .'<blockquote>quoted text</blockquote>'
        .'<h1>heading one</h1>'
        .'<h2>heading two</h2>';

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => $safeHtml,
    ]);

    expect($comment->body)->toContain('<strong>bold</strong>');
    expect($comment->body)->toContain('<em>italic</em>');
    expect($comment->body)->toContain('<a href="https://example.com">link</a>');
    expect($comment->body)->toContain('<ul>');
    expect($comment->body)->toContain('<li>item one</li>');
    expect($comment->body)->toContain('<pre><code>');
    expect($comment->body)->toContain('<blockquote>quoted text</blockquote>');
    expect($comment->body)->toContain('<h1>heading one</h1>');
    expect($comment->body)->toContain('<h2>heading two</h2>');
});

it('sanitizes comment body on update', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>Clean content</p>',
    ]);

    $comment->update([
        'body' => '<p>Updated</p><script>document.cookie</script>',
    ]);

    $comment->refresh();

    expect($comment->body)->not->toContain('<script>');
    expect($comment->body)->toContain('<p>Updated</p>');
});

it('strips javascript protocol from link href', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<a href="javascript:alert(1)">click me</a>',
    ]);

    expect($comment->body)->not->toContain('javascript:');
    expect($comment->body)->toContain('click me');
});

it('strips onclick handler from elements', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<div onclick="alert(1)">click me</div>',
    ]);

    expect($comment->body)->not->toContain('onclick');
    expect($comment->body)->toContain('click me');
});

it('sanitizes content submitted through livewire component', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    Livewire::test(Comments::class, ['model' => $post])
        ->set('newComment', '<p>Hello</p><script>alert("xss")</script>')
        ->call('addComment');

    $comment = Comment::first();

    expect($comment->body)->not->toContain('<script>');
    expect($comment->body)->toContain('<p>Hello</p>');
});
