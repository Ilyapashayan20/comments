<?php

namespace Relaticle\Comments\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Relaticle\Comments\Comment;
use Relaticle\Comments\Config;

class UserMentionedNotification extends Notification
{
    public function __construct(
        public readonly Comment $comment,
        public readonly Model $mentionedBy,
    ) {}

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
            'mentioner_name' => $this->mentionedBy->getCommentName(),
            'body' => Str::limit(strip_tags($this->comment->body), 100),
        ];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $mentionerName = $this->mentionedBy->getCommentName();

        return (new MailMessage)
            ->subject('You were mentioned in a comment')
            ->line("{$mentionerName} mentioned you in a comment:")
            ->line(Str::limit(strip_tags($this->comment->body), 200));
    }
}
