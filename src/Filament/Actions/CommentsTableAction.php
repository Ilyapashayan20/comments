<?php

namespace Relaticle\Comments\Filament\Actions;

use Filament\Actions\Action;
use Relaticle\Comments\Concerns\HasComments;
use Relaticle\Comments\Filament\Infolists\Components\CommentsEntry;

class CommentsTableAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Comments'))
            ->icon('heroicon-o-chat-bubble-left-right')
            ->slideOver()
            ->modalHeading(__('Comments'))
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->schema([
                CommentsEntry::make('comments'),
            ])
            ->badge(function (): ?int {
                $record = $this->getRecord();

                if (! $record) {
                    return null;
                }

                if (! in_array(HasComments::class, class_uses_recursive($record))) {
                    return null;
                }

                $count = $record->commentCount();

                return $count > 0 ? $count : null;
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'comments';
    }
}
