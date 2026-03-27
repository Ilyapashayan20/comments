<?php

namespace Relaticle\Comments\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Relaticle\Comments\CommentsConfig;
use Relaticle\Comments\Events\CommentReacted;
use Relaticle\Comments\Models\Comment;

class Reactions extends Component
{
    public Comment $comment;

    public bool $showPicker = false;

    public function mount(Comment $comment): void
    {
        $this->comment = $comment;
    }

    public function toggleReaction(string $reaction): void
    {
        $user = CommentsConfig::resolveAuthenticatedUser();

        if (! $user) {
            return;
        }

        if (! in_array($reaction, CommentsConfig::getAllowedReactions())) {
            return;
        }

        $existing = $this->comment->reactions()
            ->where('commenter_id', $user->getKey())
            ->where('commenter_type', $user->getMorphClass())
            ->where('reaction', $reaction)
            ->first();

        if ($existing) {
            $existing->delete();

            event(new CommentReacted($this->comment, $user, $reaction, 'removed'));
        } else {
            $this->comment->reactions()->create([
                'commenter_id' => $user->getKey(),
                'commenter_type' => $user->getMorphClass(),
                'reaction' => $reaction,
            ]);

            event(new CommentReacted($this->comment, $user, $reaction, 'added'));
        }

        unset($this->reactionSummary);

        $this->showPicker = false;
    }

    public function togglePicker(): void
    {
        $this->showPicker = ! $this->showPicker;
    }

    /** @return array<int, array{reaction: string, emoji: string, count: int, names: array<int, string>, total_reactors: int, reacted_by_user: bool}> */
    #[Computed]
    public function reactionSummary(): array
    {
        $user = CommentsConfig::resolveAuthenticatedUser();
        $userId = $user?->getKey();
        $userType = $user?->getMorphClass();

        $reactions = $this->comment->reactions()->with('commenter')->get();

        $emojiSet = CommentsConfig::getReactionEmojiSet();

        return $reactions
            ->groupBy('reaction')
            ->map(function ($group, $key) use ($emojiSet, $userId, $userType) {
                return [
                    'reaction' => $key,
                    'emoji' => $emojiSet[$key] ?? $key,
                    'count' => $group->count(),
                    'names' => $group->pluck('commenter.name')->filter()->take(3)->values()->all(),
                    'total_reactors' => $group->count(),
                    'reacted_by_user' => $group->contains(
                        fn ($r) => $r->commenter_id == $userId && $r->commenter_type === $userType
                    ),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('comments::livewire.reactions');
    }
}
