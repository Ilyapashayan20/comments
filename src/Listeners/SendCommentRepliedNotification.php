<?php

namespace Relaticle\Comments\Listeners;

use Illuminate\Support\Facades\Notification;
use Relaticle\Comments\CommentsConfig;
use Relaticle\Comments\Events\CommentCreated;
use Relaticle\Comments\Models\Subscription;
use Relaticle\Comments\Notifications\CommentRepliedNotification;

class SendCommentRepliedNotification
{
    public function handle(CommentCreated $event): void
    {
        if (! CommentsConfig::areNotificationsEnabled()) {
            return;
        }

        $comment = $event->comment;
        $commentable = $event->commentable;

        if (CommentsConfig::shouldAutoSubscribe()) {
            Subscription::subscribe($commentable, $comment->commenter);
        }

        if (! $comment->isReply()) {
            return;
        }

        $subscribers = Subscription::subscribersFor($commentable);

        $recipients = $subscribers->filter(function ($user) use ($comment) {
            return ! ($user->getMorphClass() === $comment->commenter->getMorphClass()
                && $user->getKey() === $comment->commenter->getKey());
        });

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new CommentRepliedNotification($comment));
    }
}
