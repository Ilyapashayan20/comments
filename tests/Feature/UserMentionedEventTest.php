<?php

use Relaticle\Comments\Events\UserMentioned;
use Relaticle\Comments\Models\Comment;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('carries correct comment and mentioned user in payload', function () {
    $user = User::factory()->create();
    $mentionedUser = User::factory()->create(['name' => 'john']);
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>@john</p>',
    ]);

    $event = new UserMentioned($comment, $mentionedUser);

    expect($event->comment)->toBe($comment)
        ->and($event->mentionedUser)->toBe($mentionedUser);
});
