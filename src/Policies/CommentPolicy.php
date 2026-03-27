<?php

namespace Relaticle\Comments\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Relaticle\Comments\Models\Comment;

class CommentPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return true;
    }

    public function update(Authenticatable $user, Comment $comment): bool
    {
        return $user->getKey() === $comment->commenter_id
            && $user->getMorphClass() === $comment->commenter_type;
    }

    public function delete(Authenticatable $user, Comment $comment): bool
    {
        return $user->getKey() === $comment->commenter_id
            && $user->getMorphClass() === $comment->commenter_type;
    }

    public function reply(Authenticatable $user, Comment $comment): bool
    {
        return $comment->canReply();
    }
}
