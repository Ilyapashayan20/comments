<?php

namespace Relaticle\Comments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Relaticle\Comments\CommentsConfig;

class Attachment extends Model
{
    protected $fillable = [
        'comment_id',
        'file_path',
        'original_name',
        'mime_type',
        'size',
        'disk',
    ];

    public function getTable(): string
    {
        return CommentsConfig::getTableName('attachments');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(CommentsConfig::getCommentModel());
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->file_path);
    }

    public function formattedSize(): string
    {
        return Number::fileSize($this->size);
    }
}
