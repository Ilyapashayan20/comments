<?php

namespace Relaticle\Comments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class CommentSubscription extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'user_type',
        'user_id',
    ];

    public function getTable(): string
    {
        return 'comment_subscriptions';
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    public static function isSubscribed(Model $commentable, Model $user): bool
    {
        return static::where([
            'commentable_type' => $commentable->getMorphClass(),
            'commentable_id' => $commentable->getKey(),
            'user_type' => $user->getMorphClass(),
            'user_id' => $user->getKey(),
        ])->exists();
    }

    public static function subscribe(Model $commentable, Model $user): void
    {
        static::firstOrCreate([
            'commentable_type' => $commentable->getMorphClass(),
            'commentable_id' => $commentable->getKey(),
            'user_type' => $user->getMorphClass(),
            'user_id' => $user->getKey(),
        ]);
    }

    public static function unsubscribe(Model $commentable, Model $user): void
    {
        static::where([
            'commentable_type' => $commentable->getMorphClass(),
            'commentable_id' => $commentable->getKey(),
            'user_type' => $user->getMorphClass(),
            'user_id' => $user->getKey(),
        ])->delete();
    }

    /** @return Collection<int, Model> */
    public static function subscribersFor(Model $commentable): Collection
    {
        return static::where([
            'commentable_type' => $commentable->getMorphClass(),
            'commentable_id' => $commentable->getKey(),
        ])->with('user')->get()->pluck('user')->filter()->values();
    }
}
