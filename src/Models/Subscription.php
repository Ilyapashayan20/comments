<?php

namespace Relaticle\Comments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Relaticle\Comments\CommentsConfig;

class Subscription extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'commenter_type',
        'commenter_id',
    ];

    public function getTable(): string
    {
        return CommentsConfig::getTableName('subscriptions');
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function commenter(): MorphTo
    {
        return $this->morphTo();
    }

    public static function isSubscribed(Model $commentable, Model $user): bool
    {
        return static::where([
            'commentable_type' => $commentable->getMorphClass(),
            'commentable_id' => $commentable->getKey(),
            'commenter_type' => $user->getMorphClass(),
            'commenter_id' => $user->getKey(),
        ])->exists();
    }

    public static function subscribe(Model $commentable, Model $user): void
    {
        static::firstOrCreate([
            'commentable_type' => $commentable->getMorphClass(),
            'commentable_id' => $commentable->getKey(),
            'commenter_type' => $user->getMorphClass(),
            'commenter_id' => $user->getKey(),
        ]);
    }

    public static function unsubscribe(Model $commentable, Model $user): void
    {
        static::where([
            'commentable_type' => $commentable->getMorphClass(),
            'commentable_id' => $commentable->getKey(),
            'commenter_type' => $user->getMorphClass(),
            'commenter_id' => $user->getKey(),
        ])->delete();
    }

    /** @return Collection<int, Model> */
    public static function subscribersFor(Model $commentable): Collection
    {
        return static::where([
            'commentable_type' => $commentable->getMorphClass(),
            'commentable_id' => $commentable->getKey(),
        ])->with('commenter')->get()->pluck('commenter')->filter()->values();
    }
}
