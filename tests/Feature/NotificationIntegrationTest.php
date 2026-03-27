<?php

use Illuminate\Support\Facades\Notification;
use Relaticle\Comments\Events\CommentCreated;
use Relaticle\Comments\Events\UserMentioned;
use Relaticle\Comments\Listeners\SendCommentRepliedNotification;
use Relaticle\Comments\Listeners\SendUserMentionedNotification;
use Relaticle\Comments\Models\Comment;
use Relaticle\Comments\Models\Subscription;
use Relaticle\Comments\Notifications\CommentRepliedNotification;
use Relaticle\Comments\Notifications\UserMentionedNotification;
use Relaticle\Comments\Tests\Models\Post;
use Relaticle\Comments\Tests\Models\User;

it('sends CommentRepliedNotification to parent comment author when reply is created', function () {
    Notification::fake();

    $parentAuthor = User::factory()->create();
    $replyAuthor = User::factory()->create();
    $post = Post::factory()->create();

    Subscription::subscribe($post, $parentAuthor);

    $parentComment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $parentAuthor->getKey(),
        'commenter_type' => $parentAuthor->getMorphClass(),
        'body' => '<p>Parent comment</p>',
    ]);

    $reply = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $replyAuthor->getKey(),
        'commenter_type' => $replyAuthor->getMorphClass(),
        'parent_id' => $parentComment->id,
        'body' => '<p>A reply</p>',
    ]);

    $listener = new SendCommentRepliedNotification;
    $listener->handle(new CommentCreated($reply));

    Notification::assertSentTo($parentAuthor, CommentRepliedNotification::class);
});

it('does NOT send reply notification for top-level comments', function () {
    Notification::fake();

    $author = User::factory()->create();
    $subscriber = User::factory()->create();
    $post = Post::factory()->create();

    Subscription::subscribe($post, $subscriber);

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $author->getKey(),
        'commenter_type' => $author->getMorphClass(),
        'body' => '<p>Top-level comment</p>',
    ]);

    $listener = new SendCommentRepliedNotification;
    $listener->handle(new CommentCreated($comment));

    Notification::assertNothingSent();
});

it('does NOT send reply notification to the reply author', function () {
    Notification::fake();

    $user = User::factory()->create();
    $post = Post::factory()->create();

    Subscription::subscribe($post, $user);

    $parentComment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'body' => '<p>My comment</p>',
    ]);

    $reply = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $user->getKey(),
        'commenter_type' => $user->getMorphClass(),
        'parent_id' => $parentComment->id,
        'body' => '<p>My own reply</p>',
    ]);

    $listener = new SendCommentRepliedNotification;
    $listener->handle(new CommentCreated($reply));

    Notification::assertNotSentTo($user, CommentRepliedNotification::class);
});

it('sends UserMentionedNotification when a user is mentioned', function () {
    Notification::fake();

    $author = User::factory()->create();
    $mentioned = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $author->getKey(),
        'commenter_type' => $author->getMorphClass(),
        'body' => '<p>Hey @someone</p>',
    ]);

    $listener = new SendUserMentionedNotification;
    $listener->handle(new UserMentioned($comment, $mentioned));

    Notification::assertSentTo($mentioned, UserMentionedNotification::class);
});

it('does NOT send mention notification to the comment author', function () {
    Notification::fake();

    $author = User::factory()->create();
    $post = Post::factory()->create();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $author->getKey(),
        'commenter_type' => $author->getMorphClass(),
        'body' => '<p>Hey @myself</p>',
    ]);

    $listener = new SendUserMentionedNotification;
    $listener->handle(new UserMentioned($comment, $author));

    Notification::assertNotSentTo($author, UserMentionedNotification::class);
});

it('does NOT send reply notification to unsubscribed user', function () {
    Notification::fake();

    $author = User::factory()->create();
    $unsubscribedUser = User::factory()->create();
    $post = Post::factory()->create();

    Subscription::subscribe($post, $unsubscribedUser);
    Subscription::unsubscribe($post, $unsubscribedUser);

    $parentComment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $unsubscribedUser->getKey(),
        'commenter_type' => $unsubscribedUser->getMorphClass(),
        'body' => '<p>Original</p>',
    ]);

    $reply = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $author->getKey(),
        'commenter_type' => $author->getMorphClass(),
        'parent_id' => $parentComment->id,
        'body' => '<p>Reply</p>',
    ]);

    $listener = new SendCommentRepliedNotification;
    $listener->handle(new CommentCreated($reply));

    Notification::assertNotSentTo($unsubscribedUser, CommentRepliedNotification::class);
});

it('auto-subscribes the comment author when creating a comment', function () {
    Notification::fake();

    $author = User::factory()->create();
    $post = Post::factory()->create();

    expect(Subscription::isSubscribed($post, $author))->toBeFalse();

    $comment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $author->getKey(),
        'commenter_type' => $author->getMorphClass(),
        'body' => '<p>My comment</p>',
    ]);

    $listener = new SendCommentRepliedNotification;
    $listener->handle(new CommentCreated($comment));

    expect(Subscription::isSubscribed($post, $author))->toBeTrue();
});

it('suppresses all notifications when notifications are disabled via config', function () {
    Notification::fake();
    config()->set('comments.notifications.enabled', false);

    $author = User::factory()->create();
    $subscriber = User::factory()->create();
    $mentioned = User::factory()->create();
    $post = Post::factory()->create();

    Subscription::subscribe($post, $subscriber);

    $parentComment = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $subscriber->getKey(),
        'commenter_type' => $subscriber->getMorphClass(),
        'body' => '<p>Original</p>',
    ]);

    $reply = Comment::factory()->create([
        'commentable_id' => $post->id,
        'commentable_type' => $post->getMorphClass(),
        'commenter_id' => $author->getKey(),
        'commenter_type' => $author->getMorphClass(),
        'parent_id' => $parentComment->id,
        'body' => '<p>Reply</p>',
    ]);

    $replyListener = new SendCommentRepliedNotification;
    $replyListener->handle(new CommentCreated($reply));

    $mentionListener = new SendUserMentionedNotification;
    $mentionListener->handle(new UserMentioned($reply, $mentioned));

    Notification::assertNothingSent();
});
