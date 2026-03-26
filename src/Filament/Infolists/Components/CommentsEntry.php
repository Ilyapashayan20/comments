<?php

namespace Relaticle\Comments\Filament\Infolists\Components;

use Filament\Infolists\Components\Entry;

class CommentsEntry extends Entry
{
    protected string $view = 'comments::filament.infolists.components.comments-entry';

    protected function setUp(): void
    {
        parent::setUp();

        $this->columnSpanFull();
    }
}
