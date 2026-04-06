<?php

namespace Relaticle\Comments\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Relaticle\Comments\CommentsConfig;
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
        return $this->belongsToCurrentTenant($comment)
            && $user->getKey() === $comment->commenter_id
            && $user->getMorphClass() === $comment->commenter_type;
    }

    public function delete(Authenticatable $user, Comment $comment): bool
    {
        return $this->belongsToCurrentTenant($comment)
            && $user->getKey() === $comment->commenter_id
            && $user->getMorphClass() === $comment->commenter_type;
    }

    /**
     * Verify the comment belongs to the currently active tenant.
     *
     * When multi-tenancy is disabled, this always returns true so the existing
     * ownership checks are the only gate. When enabled, a comment from another
     * tenant must never pass authorisation even if the commenter IDs happen to match.
     */
    private function belongsToCurrentTenant(Comment $comment): bool
    {
        if (! CommentsConfig::isMultiTenancyEnabled()) {
            return true;
        }

        $tenantId = CommentsConfig::resolveTenantId();

        if ($tenantId === null) {
            return true;
        }

        $column = CommentsConfig::getTenantColumn();

        return (string) $comment->{$column} === (string) $tenantId;
    }

    public function reply(Authenticatable $user, Comment $comment): bool
    {
        return $comment->canReply();
    }
}
