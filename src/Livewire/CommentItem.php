<?php

namespace Relaticle\Comments\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Relaticle\Comments\CommentsConfig;
use Relaticle\Comments\Contracts\MentionResolver;
use Relaticle\Comments\Events\CommentCreated;
use Relaticle\Comments\Events\CommentDeleted;
use Relaticle\Comments\Events\CommentUpdated;
use Relaticle\Comments\Mentions\MentionParser;
use Relaticle\Comments\Models\Comment;

class CommentItem extends Component
{
    use WithFileUploads;

    public Comment $comment;

    public bool $isEditing = false;

    public bool $isReplying = false;

    public string $editBody = '';

    public string $replyBody = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $replyAttachments = [];

    public function mount(Comment $comment): void
    {
        $this->comment = $comment;
    }

    public function startEdit(): void
    {
        $this->authorize('update', $this->comment);

        $this->isEditing = true;
        $this->editBody = $this->comment->body;
    }

    public function cancelEdit(): void
    {
        $this->isEditing = false;
        $this->editBody = '';
    }

    public function saveEdit(): void
    {
        $this->authorize('update', $this->comment);

        $this->validate([
            'editBody' => ['required', 'string', 'min:1'],
        ]);

        $this->comment->update([
            'body' => $this->editBody,
            'edited_at' => now(),
        ]);

        event(new CommentUpdated($this->comment->fresh()));

        app(MentionParser::class)->syncMentions($this->comment->fresh());

        $this->dispatch('commentUpdated');

        $this->isEditing = false;
        $this->editBody = '';
    }

    public function deleteComment(): void
    {
        $this->authorize('delete', $this->comment);

        $this->comment->delete();

        event(new CommentDeleted($this->comment));

        $this->dispatch('commentDeleted');
    }

    public function startReply(): void
    {
        if (! $this->comment->canReply()) {
            return;
        }

        $this->isReplying = true;
    }

    public function cancelReply(): void
    {
        $this->isReplying = false;
        $this->replyBody = '';
        $this->replyAttachments = [];
    }

    public function addReply(): void
    {
        $this->authorize('reply', $this->comment);

        $rules = ['replyBody' => ['required', 'string', 'min:1']];

        if (CommentsConfig::areAttachmentsEnabled()) {
            $maxSize = CommentsConfig::getAttachmentMaxSize();
            $allowedTypes = implode(',', CommentsConfig::getAttachmentAllowedTypes());
            $rules['replyAttachments.*'] = ['nullable', 'file', "max:{$maxSize}", "mimetypes:{$allowedTypes}"];
        }

        $this->validate($rules);

        $user = CommentsConfig::resolveAuthenticatedUser();

        $reply = $this->comment->commentable->comments()->create([
            'body' => $this->replyBody,
            'parent_id' => $this->comment->id,
            'commenter_id' => $user->getKey(),
            'commenter_type' => $user->getMorphClass(),
        ]);

        if (CommentsConfig::areAttachmentsEnabled() && ! empty($this->replyAttachments)) {
            $disk = CommentsConfig::getAttachmentDisk();

            foreach ($this->replyAttachments as $file) {
                $path = $file->store("comments/attachments/{$reply->id}", $disk);

                $reply->attachments()->create([
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'disk' => $disk,
                ]);
            }
        }

        event(new CommentCreated($reply));

        app(MentionParser::class)->syncMentions($reply);

        $this->dispatch('commentUpdated');

        $this->isReplying = false;
        $this->replyBody = '';
        $this->replyAttachments = [];
    }

    public function removeReplyAttachment(int $index): void
    {
        $attachments = $this->replyAttachments;
        unset($attachments[$index]);
        $this->replyAttachments = array_values($attachments);
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
                'name' => $user->getCommentDisplayName(),
                'avatar_url' => $user->getCommentAvatarUrl(),
            ])
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('comments::livewire.comment-item');
    }
}
