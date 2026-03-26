<?php

namespace Relaticle\Comments\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Relaticle\Comments\Comment;
use Relaticle\Comments\Config;
use Relaticle\Comments\Events\CommentReacted;

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
        $user = Config::resolveAuthenticatedUser();

        if (! $user) {
            return;
        }

        if (! in_array($reaction, Config::getAllowedReactions())) {
            return;
        }

        $existing = $this->comment->reactions()
            ->where('user_id', $user->getKey())
            ->where('user_type', $user->getMorphClass())
            ->where('reaction', $reaction)
            ->first();

        if ($existing) {
            $existing->delete();

            event(new CommentReacted($this->comment, $user, $reaction, 'removed'));
        } else {
            $this->comment->reactions()->create([
                'user_id' => $user->getKey(),
                'user_type' => $user->getMorphClass(),
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
        $user = Config::resolveAuthenticatedUser();
        $userId = $user?->getKey();
        $userType = $user?->getMorphClass();

        $reactions = $this->comment->reactions()->with('user')->get();

        $emojiSet = Config::getReactionEmojiSet();

        return $reactions
            ->groupBy('reaction')
            ->map(function ($group, $key) use ($emojiSet, $userId, $userType) {
                return [
                    'reaction' => $key,
                    'emoji' => $emojiSet[$key] ?? $key,
                    'count' => $group->count(),
                    'names' => $group->pluck('user.name')->filter()->take(3)->values()->all(),
                    'total_reactors' => $group->count(),
                    'reacted_by_user' => $group->contains(
                        fn ($r) => $r->user_id == $userId && $r->user_type === $userType
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
