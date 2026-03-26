<?php

namespace Relaticle\Comments\Listeners;

use Illuminate\Support\Facades\Notification;
use Relaticle\Comments\CommentSubscription;
use Relaticle\Comments\Config;
use Relaticle\Comments\Events\CommentCreated;
use Relaticle\Comments\Notifications\CommentRepliedNotification;

class SendCommentRepliedNotification
{
    public function handle(CommentCreated $event): void
    {
        if (! Config::areNotificationsEnabled()) {
            return;
        }

        $comment = $event->comment;
        $commentable = $event->commentable;

        if (Config::shouldAutoSubscribe()) {
            CommentSubscription::subscribe($commentable, $comment->user);
        }

        if (! $comment->isReply()) {
            return;
        }

        $subscribers = CommentSubscription::subscribersFor($commentable);

        $recipients = $subscribers->filter(function ($user) use ($comment) {
            return ! ($user->getMorphClass() === $comment->user->getMorphClass()
                && $user->getKey() === $comment->user->getKey());
        });

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new CommentRepliedNotification($comment));
    }
}
