<?php

namespace Relaticle\Comments\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Relaticle\Comments\CommentsConfig;

trait HasComments
{
    public function comments(): MorphMany
    {
        return $this->morphMany(CommentsConfig::getCommentModel(), 'commentable');
    }

    public function topLevelComments(): MorphMany
    {
        return $this->comments()->whereNull('parent_id');
    }

    public function commentCount(): int
    {
        return $this->comments()->count();
    }
}
