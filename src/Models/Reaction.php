<?php

namespace Relaticle\Comments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Relaticle\Comments\CommentsConfig;

class Reaction extends Model
{
    protected $fillable = [
        'comment_id',
        'commenter_id',
        'commenter_type',
        'reaction',
    ];

    public function getTable(): string
    {
        return CommentsConfig::getTableName('reactions');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(CommentsConfig::getCommentModel());
    }

    public function commenter(): MorphTo
    {
        return $this->morphTo();
    }
}
