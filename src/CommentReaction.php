<?php

namespace Relaticle\Comments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommentReaction extends Model
{
    protected $fillable = [
        'comment_id',
        'user_id',
        'user_type',
        'reaction',
    ];

    public function getTable(): string
    {
        return 'comment_reactions';
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Config::getCommentModel());
    }

    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
