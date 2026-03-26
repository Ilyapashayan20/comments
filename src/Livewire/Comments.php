<?php

namespace Relaticle\Comments\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Relaticle\Comments\Comment;
use Relaticle\Comments\CommentSubscription;
use Relaticle\Comments\Config;
use Relaticle\Comments\Contracts\MentionResolver;
use Relaticle\Comments\Events\CommentCreated;
use Relaticle\Comments\Mentions\MentionParser;

class Comments extends Component
{
    use WithFileUploads;

    public Model $model;

    public string $newComment = '';

    public string $sortDirection = 'asc';

    /** @var array<int, TemporaryUploadedFile> */
    public array $attachments = [];

    public int $perPage = 10;

    public int $loadedCount = 10;

    public function mount(Model $model): void
    {
        $this->model = $model;
        $this->perPage = Config::getPerPage();
        $this->loadedCount = $this->perPage;
    }

    /** @return Collection<int, Comment> */
    #[Computed]
    public function comments(): Collection
    {
        return $this->model
            ->topLevelComments()
            ->with(['user', 'mentions', 'attachments', 'reactions.user', 'replies.user', 'replies.mentions', 'replies.attachments', 'replies.reactions.user'])
            ->orderBy('created_at', $this->sortDirection)
            ->take($this->loadedCount)
            ->get();
    }

    #[Computed]
    public function totalCount(): int
    {
        return $this->model->topLevelComments()->count();
    }

    #[Computed]
    public function hasMore(): bool
    {
        return $this->totalCount > $this->loadedCount;
    }

    #[Computed]
    public function isSubscribed(): bool
    {
        $user = Config::resolveAuthenticatedUser();

        if (! $user) {
            return false;
        }

        return CommentSubscription::isSubscribed($this->model, $user);
    }

    public function toggleSubscription(): void
    {
        $user = Config::resolveAuthenticatedUser();

        if (! $user) {
            return;
        }

        if ($this->isSubscribed) {
            CommentSubscription::unsubscribe($this->model, $user);
        } else {
            CommentSubscription::subscribe($this->model, $user);
        }

        unset($this->isSubscribed);
    }

    public function addComment(): void
    {
        $rules = ['newComment' => ['required', 'string', 'min:1']];

        if (Config::areAttachmentsEnabled()) {
            $maxSize = Config::getAttachmentMaxSize();
            $allowedTypes = implode(',', Config::getAttachmentAllowedTypes());
            $rules['attachments.*'] = ['nullable', 'file', "max:{$maxSize}", "mimetypes:{$allowedTypes}"];
        }

        $this->validate($rules);

        $this->authorize('create', Config::getCommentModel());

        $user = Config::resolveAuthenticatedUser();

        $comment = $this->model->comments()->create([
            'body' => $this->newComment,
            'user_id' => $user->getKey(),
            'user_type' => $user->getMorphClass(),
        ]);

        if (Config::areAttachmentsEnabled() && ! empty($this->attachments)) {
            $disk = Config::getAttachmentDisk();

            foreach ($this->attachments as $file) {
                $path = $file->store("comments/attachments/{$comment->id}", $disk);

                $comment->attachments()->create([
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'disk' => $disk,
                ]);
            }
        }

        event(new CommentCreated($comment));

        app(MentionParser::class)->syncMentions($comment);

        $this->reset('newComment', 'attachments');
    }

    public function removeAttachment(int $index): void
    {
        $attachments = $this->attachments;
        unset($attachments[$index]);
        $this->attachments = array_values($attachments);
    }

    public function loadMore(): void
    {
        $this->loadedCount += $this->perPage;
    }

    public function toggleSort(): void
    {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    }

    /** @return array<string, string> */
    public function getListeners(): array
    {
        $listeners = [
            'commentDeleted' => 'refreshComments',
            'commentUpdated' => 'refreshComments',
        ];

        if (Config::isBroadcastingEnabled()) {
            $prefix = Config::getBroadcastChannelPrefix();
            $type = $this->model->getMorphClass();
            $id = $this->model->getKey();
            $channel = "echo-private:{$prefix}.{$type}.{$id}";

            $listeners["{$channel},CommentCreated"] = 'refreshComments';
            $listeners["{$channel},CommentUpdated"] = 'refreshComments';
            $listeners["{$channel},CommentDeleted"] = 'refreshComments';
            $listeners["{$channel},CommentReacted"] = 'refreshComments';
        }

        return $listeners;
    }

    public function refreshComments(): void
    {
        unset($this->comments, $this->totalCount, $this->hasMore);
    }

    /** @return array<int, array{id: int, name: string, avatar_url: ?string}> */
    public function searchUsers(string $query): array
    {
        if (mb_strlen($query) < 1) {
            return [];
        }

        $resolver = app(MentionResolver::class);

        return $resolver->search($query)
            ->map(fn ($user) => [
                'id' => $user->getKey(),
                'name' => $user->getCommentName(),
                'avatar_url' => $user->getCommentAvatarUrl(),
            ])
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('comments::livewire.comments');
    }
}
