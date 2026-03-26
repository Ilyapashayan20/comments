<?php

namespace Relaticle\Comments\Events;

use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Relaticle\Comments\Comment;
use Relaticle\Comments\Config;

class CommentReacted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithBroadcasting;
    use SerializesModels;

    public function __construct(
        public readonly Comment $comment,
        public readonly object $user,
        public readonly string $reaction,
        public readonly string $action,
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        $prefix = Config::getBroadcastChannelPrefix();

        return [
            new PrivateChannel("{$prefix}.{$this->comment->commentable_type}.{$this->comment->commentable_id}"),
        ];
    }

    public function broadcastWhen(): bool
    {
        return Config::isBroadcastingEnabled();
    }

    /** @return array{comment_id: int|string, reaction: string, action: string} */
    public function broadcastWith(): array
    {
        return [
            'comment_id' => $this->comment->id,
            'reaction' => $this->reaction,
            'action' => $this->action,
        ];
    }
}
