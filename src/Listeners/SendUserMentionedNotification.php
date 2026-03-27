<?php

namespace Relaticle\Comments\Listeners;

use Relaticle\Comments\CommentsConfig;
use Relaticle\Comments\Events\UserMentioned;
use Relaticle\Comments\Models\Subscription;
use Relaticle\Comments\Notifications\UserMentionedNotification;

class SendUserMentionedNotification
{
    public function handle(UserMentioned $event): void
    {
        if (! CommentsConfig::areNotificationsEnabled()) {
            return;
        }

        $comment = $event->comment;
        $mentionedUser = $event->mentionedUser;

        if (CommentsConfig::shouldAutoSubscribe()) {
            Subscription::subscribe($comment->commentable, $mentionedUser);
        }

        $isSelf = $mentionedUser->getMorphClass() === $comment->commenter->getMorphClass()
            && $mentionedUser->getKey() === $comment->commenter->getKey();

        if ($isSelf) {
            return;
        }

        $mentionedUser->notify(new UserMentionedNotification($comment, $comment->commenter));
    }
}
