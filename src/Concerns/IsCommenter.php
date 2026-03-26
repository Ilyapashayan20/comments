<?php

namespace Relaticle\Comments\Concerns;

use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;

trait IsCommenter
{
    public function getCommentName(): string
    {
        if ($this instanceof HasName) {
            return $this->getFilamentName();
        }

        return $this->name ?? 'Unknown';
    }

    public function getCommentAvatarUrl(): ?string
    {
        if ($this instanceof HasAvatar) {
            return $this->getFilamentAvatarUrl();
        }

        return null;
    }
}
