---
name: comments-development
description: Full-featured commenting system for Filament panels with polymorphic comments, threaded replies, @mentions, emoji reactions, file attachments, and real-time notifications. Use when adding the HasComments trait to models, integrating CommentsAction/CommentsTableAction/CommentsEntry in Filament, configuring threading, reactions, mentions, attachments, notifications, broadcasting, or customizing the CommentPolicy.
---

# Comments Development

## When to Use This Skill

Use when:
- Adding commenting capability to an Eloquent model
- Integrating comments into Filament resources (actions, table actions, infolists)
- Configuring threading depth, reactions, mentions, or attachments
- Working with comment notifications and subscriptions
- Customizing the CommentPolicy for authorization
- Implementing real-time updates via broadcasting or polling
- Creating a custom MentionResolver

## Quick Start

### 1. Add Traits to Models

```php
use Relaticle\Comments\Concerns\HasComments;
use Relaticle\Comments\Contracts\Commentable;

class Project extends Model implements Commentable
{
    use HasComments;
}
```

```php
use Relaticle\Comments\Concerns\IsCommenter;
use Relaticle\Comments\Contracts\Commenter;

class User extends Authenticatable implements Commenter
{
    use IsCommenter;
}
```

### 2. Register Plugin in Panel

```php
use Relaticle\Comments\CommentsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            CommentsPlugin::make(),
        ]);
}
```

### 3. Publish and Run Migrations

```bash
php artisan vendor:publish --tag=comments-migrations
php artisan migrate
```

## Filament Integration

### Slide-Over Action (View/Edit Pages)

```php
use Relaticle\Comments\Filament\Actions\CommentsAction;

protected function getHeaderActions(): array
{
    return [
        CommentsAction::make(),
    ];
}
```

Shows a comment count badge and opens a slide-over modal with the full comment thread.

### Table Row Action

```php
use Relaticle\Comments\Filament\Actions\CommentsTableAction;

public static function table(Table $table): Table
{
    return $table
        ->actions([
            CommentsTableAction::make(),
        ]);
}
```

### Infolist Entry

```php
use Relaticle\Comments\Filament\Infolists\Components\CommentsEntry;

public static function infolist(Infolist $infolist): Infolist
{
    return $infolist->schema([
        CommentsEntry::make('comments'),
    ]);
}
```

Embeds the full comment component inline within an infolist.

## Configuration Reference

Publish config: `php artisan vendor:publish --tag=comments-config`

| Key | Default | Purpose |
|-----|---------|---------|
| `tables.comments` | `'comments'` | Main comments table name |
| `models.comment` | `Comment::class` | Comment model class |
| `commenter.model` | `User::class` | Commenter (user) model class |
| `policy` | `CommentPolicy::class` | Authorization policy class |
| `threading.max_depth` | `2` | Maximum reply nesting depth |
| `pagination.per_page` | `10` | Comments per page |
| `reactions.emoji_set` | 6 emojis | Available reaction emojis |
| `mentions.resolver` | `DefaultMentionResolver::class` | User search resolver |
| `mentions.max_results` | `5` | Autocomplete results limit |
| `editor.toolbar` | bold, italic, strike, link, lists, codeBlock | Rich text toolbar buttons |
| `notifications.enabled` | `true` | Enable/disable notifications |
| `notifications.channels` | `['database']` | Notification channels |
| `subscriptions.auto_subscribe` | `true` | Auto-subscribe on comment/mention |
| `attachments.enabled` | `true` | Enable file attachments |
| `attachments.disk` | `'public'` | Storage disk |
| `attachments.max_size` | `10240` | Max file size (KB) |
| `attachments.allowed_types` | images, pdf, text, word | Allowed MIME types |
| `broadcasting.enabled` | `false` | Enable real-time broadcasting |
| `broadcasting.channel_prefix` | `'comments'` | Private channel prefix |
| `polling.interval` | `'10s'` | Livewire polling interval (fallback) |

### Default Reactions

```php
'reactions' => [
    'emoji_set' => [
        'thumbs_up' => ['emoji' => "\u{1F44D}", 'label' => 'Like'],
        'heart' => ['emoji' => "\u{2764}\u{FE0F}", 'label' => 'Love'],
        'celebrate' => ['emoji' => "\u{1F389}", 'label' => 'Celebrate'],
        'laugh' => ['emoji' => "\u{1F602}", 'label' => 'Laugh'],
        'thinking' => ['emoji' => "\u{1F914}", 'label' => 'Thinking'],
        'sad' => ['emoji' => "\u{1F622}", 'label' => 'Sad'],
    ],
],
```

## Events

| Event | Broadcast | Payload |
|-------|-----------|---------|
| `CommentCreated` | Yes | `$comment` |
| `CommentUpdated` | Yes | `$comment` |
| `CommentDeleted` | Yes | `$comment` |
| `CommentReacted` | Yes | `$comment`, `$user`, `$reaction`, `$action` (added/removed) |
| `UserMentioned` | No | `$comment`, `$mentionedUser` |

Broadcasting uses private channels: `{prefix}.{commentable_type}.{commentable_id}`

## Authorization

Default `CommentPolicy` methods:

| Method | Default | Description |
|--------|---------|-------------|
| `viewAny()` | `true` | Everyone can view comments |
| `create()` | `true` | Everyone can create comments |
| `update()` | Owner only | Only comment author can edit |
| `delete()` | Owner only | Only comment author can delete |
| `reply()` | Depth check | Can reply if max_depth not exceeded |

### Custom Policy

```php
// config/comments.php
'policy' => App\Policies\CustomCommentPolicy::class,
```

```php
namespace App\Policies;

use Relaticle\Comments\Comment;
use Relaticle\Comments\Contracts\Commenter;

class CustomCommentPolicy
{
    public function delete(Commenter $user, Comment $comment): bool
    {
        return $comment->user_id === $user->getKey()
            || $user->hasRole('admin');
    }
}
```

## Common Patterns

### Scoped Comments (Multi-tenancy)

```php
use Relaticle\Comments\Config;

// In AppServiceProvider::boot()
Config::resolveAuthenticatedUserUsing(function () {
    return auth()->user();
});
```

### Custom Mention Resolver

```php
use Relaticle\Comments\Contracts\MentionResolver;

class TeamMentionResolver implements MentionResolver
{
    public function search(string $query): Collection
    {
        return User::query()
            ->where('team_id', auth()->user()->team_id)
            ->where('name', 'like', "{$query}%")
            ->limit(config('comments.mentions.max_results'))
            ->get();
    }

    public function resolveByNames(array $names): Collection
    {
        return User::query()
            ->where('team_id', auth()->user()->team_id)
            ->whereIn('name', $names)
            ->get();
    }
}
```

Register in config:

```php
// config/comments.php
'mentions' => [
    'resolver' => App\Comments\TeamMentionResolver::class,
],
```

### Enable Broadcasting

```php
// config/comments.php
'broadcasting' => [
    'enabled' => true,
    'channel_prefix' => 'comments',
],
```

When broadcasting is enabled, the Livewire component listens for real-time events. When disabled, it falls back to polling at the configured interval.

## Database Schema

Five tables are created:

- `comments` -- Polymorphic comments with parent_id for threading, soft deletes
- `comment_reactions` -- Unique reactions per user+comment+emoji
- `comment_mentions` -- Tracks @mentioned users per comment
- `comment_subscriptions` -- Thread subscription tracking per user+commentable
- `comment_attachments` -- File metadata (path, name, MIME type, size, disk)

## Model Relationships

```php
// On any Commentable model
$model->comments();           // All comments (morphMany)
$model->topLevelComments();   // Top-level only (no parent)
$model->commentCount();       // Total count

// On Comment model
$comment->commentable();      // Parent model (morphTo)
$comment->user();             // Commenter (morphTo)
$comment->parent();           // Parent comment (belongsTo)
$comment->replies();          // Child comments (hasMany)
$comment->reactions();        // Reactions (hasMany)
$comment->attachments();      // File attachments (hasMany)
$comment->mentions();         // Mentioned users (morphToMany)
```
