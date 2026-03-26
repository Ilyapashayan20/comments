<?php

use Livewire\Livewire;
use Relaticle\Comments\Comment;
use Relaticle\Comments\Config;
use Relaticle\Comments\Livewire\CommentItem;
use Relaticle\Comments\Livewire\Comments;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('creates a comment with rich HTML content preserved', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user);

    $html = '<p>Hello <strong>bold</strong> and <em>italic</em> world</p>';

    Livewire::test(Comments::class, ['model' => $post])
        ->set('newComment', $html)
        ->call('addComment');

    $comment = Comment::first();

    expect($comment->body)->toContain('<strong>bold</strong>');
    expect($comment->body)->toContain('<em>italic</em>');
});

it('pre-fills editBody with existing comment HTML when starting edit', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $originalHtml = '<p>Hello <strong>world</strong></p>';

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'body' => $originalHtml,
    ]);

    $this->actingAs($user);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('startEdit')
        ->assertSet('editBody', $originalHtml);
});

it('saves edited HTML content through edit form', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'body' => '<p>Original</p>',
    ]);

    $this->actingAs($user);

    $updatedHtml = '<p>Updated with <strong>bold</strong> and <a href="https://example.com">a link</a></p>';

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('startEdit')
        ->set('editBody', $updatedHtml)
        ->call('saveEdit');

    $comment->refresh();

    expect($comment->body)->toContain('<strong>bold</strong>');
    expect($comment->body)->toContain('<a href="https://example.com">a link</a>');
});

it('creates reply with rich HTML content', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
    ]);

    $this->actingAs($user);

    $replyHtml = '<p>Reply with <em>emphasis</em> and <code>inline code</code></p>';

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->call('startReply')
        ->set('replyBody', $replyHtml)
        ->call('addReply');

    $reply = Comment::where('parent_id', $comment->id)->first();

    expect($reply)->not->toBeNull();
    expect($reply->body)->toContain('<em>emphasis</em>');
    expect($reply->body)->toContain('<code>inline code</code>');
});

it('renders comment body with fi-prose class', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'user_id' => $user->getKey(),
        'user_type' => $user->getMorphClass(),
        'body' => '<p>Styled comment</p>',
    ]);

    $this->actingAs($user);

    Livewire::test(CommentItem::class, ['comment' => $comment])
        ->assertSeeHtml('fi-prose');
});

it('returns editor toolbar configuration as nested array', function () {
    $toolbar = Config::getEditorToolbar();

    expect($toolbar)->toBeArray();
    expect($toolbar)->not->toBeEmpty();
    expect($toolbar[0])->toBeArray();
    expect($toolbar[0])->toContain('bold');
    expect($toolbar[0])->toContain('italic');
});

it('uses custom toolbar config when overridden', function () {
    config(['comments.editor.toolbar' => [
        ['bold', 'italic'],
    ]]);

    $toolbar = Config::getEditorToolbar();

    expect($toolbar)->toHaveCount(1);
    expect($toolbar[0])->toBe(['bold', 'italic']);
});
