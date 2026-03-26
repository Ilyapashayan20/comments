<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use Relaticle\Comments\Comment;
use Relaticle\Comments\Config;

it('registers the config file', function () {
    expect(config('comments'))->toBeArray();
    expect(config('comments.threading.max_depth'))->toBe(2);
    expect(config('comments.pagination.per_page'))->toBe(10);
});

it('resolves the comment model from config', function () {
    expect(Config::getCommentModel())->toBe(Comment::class);
});

it('resolves the comment table from config', function () {
    expect(Config::getCommentTable())->toBe('comments');
});

it('resolves max depth from config', function () {
    expect(Config::getMaxDepth())->toBe(2);
});

it('registers the morph map for comment', function () {
    $map = Relation::morphMap();
    expect($map)->toHaveKey('comment');
    expect($map['comment'])->toBe(Comment::class);
});

it('creates the comments table via migration', function () {
    expect(Schema::hasTable('comments'))->toBeTrue();
    expect(Schema::hasColumns('comments', [
        'id', 'commentable_type', 'commentable_id',
        'user_type', 'user_id', 'parent_id', 'body',
        'edited_at', 'deleted_at', 'created_at', 'updated_at',
    ]))->toBeTrue();
});
