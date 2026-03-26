<?php

namespace Relaticle\Comments\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Relaticle\Comments\Comment;
use Relaticle\Comments\Config;

class CommentRepliedNotification extends Notification
{
    public function __construct(public readonly Comment $comment) {}

    /** @return array<int, string> */
    public function via(mixed $notifiable): array
    {
        return Config::getNotificationChannels();
    }

    /** @return array<string, mixed> */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'comment_id' => $this->comment->id,
            'commentable_type' => $this->comment->commentable_type,
            'commentable_id' => $this->comment->commentable_id,
            'commenter_name' => $this->comment->user->getCommentName(),
            'body' => Str::limit(strip_tags($this->comment->body), 100),
        ];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $commenterName = $this->comment->user->getCommentName();

        return (new MailMessage)
            ->subject('New reply to your comment')
            ->line("{$commenterName} replied to your comment:")
            ->line(Str::limit(strip_tags($this->comment->body), 200));
    }
}
