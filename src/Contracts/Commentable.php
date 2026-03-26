<?php

namespace Relaticle\Comments\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Commentable
{
    public function comments(): MorphMany;

    public function topLevelComments(): MorphMany;

    public function commentCount(): int;
}
