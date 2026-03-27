<?php

namespace Relaticle\Comments\Contracts;

interface Commentator
{
    public function getKey();

    public function getMorphClass();

    public function getCommentDisplayName(): string;

    public function getCommentAvatarUrl(): ?string;
}
