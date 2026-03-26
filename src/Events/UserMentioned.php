<?php

namespace Relaticle\Comments\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Relaticle\Comments\Comment;

class UserMentioned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Comment $comment,
        public readonly Model $mentionedUser,
    ) {}
}
