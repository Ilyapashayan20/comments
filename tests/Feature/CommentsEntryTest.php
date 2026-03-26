<?php

use Relaticle\Comments\Filament\Infolists\Components\CommentsEntry;

it('can be instantiated via make', function () {
    $entry = CommentsEntry::make('comments');

    expect($entry)->toBeInstanceOf(CommentsEntry::class);
});

it('has the correct view path', function () {
    $entry = CommentsEntry::make('comments');

    expect($entry->getView())->toBe('comments::filament.infolists.components.comments-entry');
});

it('defaults to full column span', function () {
    $entry = CommentsEntry::make('comments');

    expect($entry->getColumnSpan('default'))->toBe('full');
});
