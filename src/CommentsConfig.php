<?php

namespace Relaticle\Comments;

use App\Models\User;
use Closure;
use Filament\Forms\Components\RichEditor\MentionProvider;
use Relaticle\Comments\Mentions\DefaultMentionResolver;
use Relaticle\Comments\Models\Comment;
use Relaticle\Comments\Policies\CommentPolicy;

class CommentsConfig
{
    protected static ?Closure $resolveAuthenticatedUser = null;

    public static function getCommentModel(): string
    {
        return config('comments.models.comment', Comment::class);
    }

    public static function getCommenterModel(): string
    {
        return config('comments.commenter.model', User::class);
    }

    public static function getCommentTable(): string
    {
        return static::getTableName('comments');
    }

    public static function getTableName(string $table): string
    {
        $defaults = [
            'comments' => 'comments',
            'reactions' => 'comment_reactions',
            'mentions' => 'comment_mentions',
            'subscriptions' => 'comment_subscriptions',
            'attachments' => 'comment_attachments',
        ];

        return config("comments.table_names.{$table}", $defaults[$table] ?? $table);
    }

    public static function getCommenterMorphName(): string
    {
        return config('comments.column_names.commenter_morph', 'commenter');
    }

    public static function getMaxDepth(): int
    {
        return (int) config('comments.threading.max_depth', 2);
    }

    public static function getPerPage(): int
    {
        return (int) config('comments.pagination.per_page', 10);
    }

    /** @return array<int, array<int, string>> */
    public static function getEditorToolbar(): array
    {
        return (array) config('comments.editor.toolbar', [
            ['bold', 'italic', 'strike', 'link'],
            ['bulletList', 'orderedList'],
            ['codeBlock'],
        ]);
    }

    public static function getPolicyClass(): string
    {
        return config('comments.policy', CommentPolicy::class);
    }

    public static function getMentionResolver(): string
    {
        return config('comments.mentions.resolver', DefaultMentionResolver::class);
    }

    public static function getMentionMaxResults(): int
    {
        return (int) config('comments.mentions.max_results', 5);
    }

    /** @return array<string, string> */
    public static function getReactionEmojiSet(): array
    {
        return (array) config('comments.reactions.emoji_set', [
            'thumbs_up' => "\u{1F44D}",
            'heart' => "\u{2764}\u{FE0F}",
            'celebrate' => "\u{1F389}",
            'laugh' => "\u{1F604}",
            'thinking' => "\u{1F914}",
            'sad' => "\u{1F622}",
        ]);
    }

    /** @return array<int, string> */
    public static function getAllowedReactions(): array
    {
        return array_keys(static::getReactionEmojiSet());
    }

    /** @return array<int, string> */
    public static function getNotificationChannels(): array
    {
        return (array) config('comments.notifications.channels', ['database']);
    }

    public static function areNotificationsEnabled(): bool
    {
        return (bool) config('comments.notifications.enabled', true);
    }

    public static function shouldAutoSubscribe(): bool
    {
        return (bool) config('comments.subscriptions.auto_subscribe', true);
    }

    public static function areAttachmentsEnabled(): bool
    {
        return (bool) config('comments.attachments.enabled', true);
    }

    public static function getAttachmentDisk(): string
    {
        return (string) config('comments.attachments.disk', 'public');
    }

    public static function getAttachmentMaxSize(): int
    {
        return (int) config('comments.attachments.max_size', 10240);
    }

    /** @return array<int, string> */
    public static function getAttachmentAllowedTypes(): array
    {
        return (array) config('comments.attachments.allowed_types', [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public static function isBroadcastingEnabled(): bool
    {
        return (bool) config('comments.broadcasting.enabled', false);
    }

    public static function getBroadcastChannelPrefix(): string
    {
        return (string) config('comments.broadcasting.channel_prefix', 'comments');
    }

    public static function getPollingInterval(): string
    {
        return (string) config('comments.polling.interval', '10s');
    }

    public static function isMultiTenancyEnabled(): bool
    {
        return (bool) config('comments.multi_tenancy.enabled', false);
    }

    public static function getTenantColumn(): string
    {
        return (string) config('comments.multi_tenancy.tenant_column', 'tenant_id');
    }

    public static function resolveTenantId(): int|string|null
    {
        $resolver = config('comments.multi_tenancy.tenant_resolver');

        if (! is_callable($resolver)) {
            return null;
        }

        return call_user_func($resolver);
    }

    public static function resolveTenantUsing(callable $callback): void
    {
        config(['comments.multi_tenancy.tenant_resolver' => $callback]);
    }

    public static function resolveAuthenticatedUser(): ?object
    {
        if (static::$resolveAuthenticatedUser) {
            return call_user_func(static::$resolveAuthenticatedUser);
        }

        return auth()->user();
    }

    public static function resolveAuthenticatedUserUsing(Closure $callback): void
    {
        static::$resolveAuthenticatedUser = $callback;
    }

    public static function makeMentionProvider(): MentionProvider
    {
        return MentionProvider::make('@')
            ->getSearchResultsUsing(fn (string $search): array => static::getCommenterModel()::query()
                ->where('name', 'like', "%{$search}%")
                ->orderBy('name')
                ->limit(static::getMentionMaxResults())
                ->pluck('name', 'id')
                ->all())
            ->getLabelsUsing(fn (array $ids): array => static::getCommenterModel()::query()
                ->whereIn('id', $ids)
                ->pluck('name', 'id')
                ->all());
    }
}
