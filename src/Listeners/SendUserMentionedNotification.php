<?php

namespace Relaticle\Comments\Listeners;

use Relaticle\Comments\CommentSubscription;
use Relaticle\Comments\Config;
use Relaticle\Comments\Events\UserMentioned;
use Relaticle\Comments\Notifications\UserMentionedNotification;

class SendUserMentionedNotification
{
    public function handle(UserMentioned $event): void
    {
        if (! Config::areNotificationsEnabled()) {
            return;
        }

        $comment = $event->comment;
        $mentionedUser = $event->mentionedUser;

        if (Config::shouldAutoSubscribe()) {
            CommentSubscription::subscribe($comment->commentable, $mentionedUser);
        }

        $isSelf = $mentionedUser->getMorphClass() === $comment->user->getMorphClass()
            && $mentionedUser->getKey() === $comment->user->getKey();

        if ($isSelf) {
            return;
        }

        $mentionedUser->notify(new UserMentionedNotification($comment, $comment->user));
    }
}
