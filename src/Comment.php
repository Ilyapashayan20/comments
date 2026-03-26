<?php

namespace Relaticle\Comments;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Relaticle\Comments\Database\Factories\CommentFactory;

class Comment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $comment): void {
            $comment->body = Str::sanitizeHtml($comment->body);
        });

        static::forceDeleting(function (self $comment): void {
            $comment->attachments()->delete();
            $comment->reactions()->delete();
            $comment->mentions()->detach();
        });
    }

    protected $fillable = [
        'body',
        'parent_id',
        'user_id',
        'user_type',
        'edited_at',
    ];

    public function getTable(): string
    {
        return Config::getCommentTable();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Config::getCommentModel(), 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Config::getCommentModel(), 'parent_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(CommentReaction::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CommentAttachment::class);
    }

    public function mentions(): MorphToMany
    {
        return $this->morphedByMany(
            Config::getCommenterModel(),
            'user',
            'comment_mentions',
            'comment_id',
            'user_id',
        );
    }

    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    public function isTopLevel(): bool
    {
        return $this->parent_id === null;
    }

    public function hasReplies(): bool
    {
        return $this->replies()->exists();
    }

    public function isEdited(): bool
    {
        return $this->edited_at !== null;
    }

    public function canReply(): bool
    {
        return $this->depth() < Config::getMaxDepth();
    }

    public function depth(): int
    {
        $depth = 0;
        $comment = $this;

        while ($comment->parent_id !== null) {
            $comment = $comment->parent;
            $depth++;

            if ($depth >= Config::getMaxDepth()) {
                return Config::getMaxDepth();
            }
        }

        return $depth;
    }

    public function renderBodyWithMentions(): string
    {
        $body = $this->body;
        $mentionNames = $this->mentions->pluck('name')->filter()->unique();

        foreach ($mentionNames as $name) {
            $escapedName = e($name);
            $styledSpan = '<span class="comment-mention inline rounded bg-primary-50 px-1 font-medium text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">@'.$escapedName.'</span>';

            $body = str_replace("&#64;{$name}", $styledSpan, $body);
            $body = str_replace("@{$name}", $styledSpan, $body);
        }

        return $body;
    }
}
