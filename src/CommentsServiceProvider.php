<?php

namespace Relaticle\Comments;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Relaticle\Comments\Contracts\MentionResolver;
use Relaticle\Comments\Events\CommentCreated;
use Relaticle\Comments\Events\UserMentioned;
use Relaticle\Comments\Listeners\SendCommentRepliedNotification;
use Relaticle\Comments\Listeners\SendUserMentionedNotification;
use Relaticle\Comments\Livewire\CommentItem;
use Relaticle\Comments\Livewire\Comments;
use Relaticle\Comments\Livewire\Reactions;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CommentsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'comments';

    public static string $viewNamespace = 'comments';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews(static::$viewNamespace)
            ->hasTranslations()
            ->hasMigrations([
                'create_comments_table',
                'create_comment_mentions_table',
                'create_comment_reactions_table',
                'create_comment_subscriptions_table',
                'create_comment_attachments_table',
            ]);
    }

    public function packageRegistered(): void
    {
        Relation::morphMap([
            'comment' => CommentsConfig::getCommentModel(),
        ]);

        $this->app->bind(
            MentionResolver::class,
            fn () => new (CommentsConfig::getMentionResolver())
        );
    }

    public function packageBooted(): void
    {
        Gate::policy(
            CommentsConfig::getCommentModel(),
            CommentsConfig::getPolicyClass(),
        );

        Event::listen(CommentCreated::class, SendCommentRepliedNotification::class);
        Event::listen(UserMentioned::class, SendUserMentionedNotification::class);

        Livewire::component('comments', Comments::class);
        Livewire::component('comment-item', CommentItem::class);
        Livewire::component('reactions', Reactions::class);
    }
}
