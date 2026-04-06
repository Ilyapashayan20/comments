<?php

use Illuminate\Support\Facades\DB;
use Relaticle\Comments\CommentsConfig;
use Relaticle\Comments\Models\Comment;
use Relaticle\Comments\Policies\CommentPolicy;
use Relaticle\Comments\Scopes\TenantScope;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

beforeEach(function () {
    config(['comments.multi_tenancy.enabled' => true]);
});

afterEach(function () {
    config(['comments.multi_tenancy.enabled' => false]);
    CommentsConfig::resolveTenantUsing(fn () => null);
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeAttrs(Post $post, User $user): array
{
    return [
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->id,
        'commenter_type' => $user->getMorphClass(),
        'commenter_id' => $user->id,
    ];
}

function insertComment(Post $post, User $user, int $tenantId): Comment
{
    // Insert directly via DB to bypass the global scope and creating listener,
    // allowing us to plant comments with arbitrary tenant IDs for testing.
    $id = DB::table('comments')->insertGetId(array_merge(makeAttrs($post, $user), [
        'body' => '<p>Test</p>',
        'tenant_id' => $tenantId,
        'created_at' => now(),
        'updated_at' => now(),
    ]));

    return Comment::withoutGlobalScope(TenantScope::class)->findOrFail($id);
}

// ── Scoping ───────────────────────────────────────────────────────────────────

it('scopes queries to the current tenant', function () {
    $post = Post::factory()->create();
    $user = User::factory()->create();

    insertComment($post, $user, 1);
    insertComment($post, $user, 2);

    CommentsConfig::resolveTenantUsing(fn () => 1);
    expect(Comment::count())->toBe(1);
    expect(Comment::first()->tenant_id)->toBe(1);
});

it('returns all comments when global scope is removed', function () {
    $post = Post::factory()->create();
    $user = User::factory()->create();

    insertComment($post, $user, 1);
    insertComment($post, $user, 2);

    CommentsConfig::resolveTenantUsing(fn () => 1);
    expect(Comment::withoutGlobalScope(TenantScope::class)->count())->toBe(2);
});

// ── Tenant stamping ───────────────────────────────────────────────────────────

it('stamps tenant_id on new comments automatically', function () {
    $post = Post::factory()->create();
    $user = User::factory()->create();

    CommentsConfig::resolveTenantUsing(fn () => 42);

    $comment = Comment::factory()->create(makeAttrs($post, $user));

    expect(Comment::withoutGlobalScope(TenantScope::class)->find($comment->id)->tenant_id)->toBe(42);
});

it('does not stamp tenant_id when multi-tenancy is disabled', function () {
    config(['comments.multi_tenancy.enabled' => false]);

    $post = Post::factory()->create();
    $user = User::factory()->create();

    CommentsConfig::resolveTenantUsing(fn () => 99);

    $comment = Comment::factory()->create(makeAttrs($post, $user));

    expect(Comment::withoutGlobalScope(TenantScope::class)->find($comment->id)->tenant_id)->toBeNull();
});

// ── Policy ────────────────────────────────────────────────────────────────────

it('allows update when user owns the comment and tenant matches', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    CommentsConfig::resolveTenantUsing(fn () => 1);
    $comment = insertComment($post, $user, 1);

    expect((new CommentPolicy)->update($user, $comment))->toBeTrue();
});

it('denies update when comment belongs to a different tenant', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    CommentsConfig::resolveTenantUsing(fn () => 1);
    $comment = insertComment($post, $user, 2);

    expect((new CommentPolicy)->update($user, $comment))->toBeFalse();
});

it('denies delete when comment belongs to a different tenant', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    CommentsConfig::resolveTenantUsing(fn () => 1);
    $comment = insertComment($post, $user, 2);

    expect((new CommentPolicy)->delete($user, $comment))->toBeFalse();
});
