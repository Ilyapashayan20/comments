<?php

namespace Relaticle\Comments\Contracts;

interface Commenter
{
    public function getKey();

    public function getMorphClass();

    public function getCommentName(): string;

    public function getCommentAvatarUrl(): ?string;
}
