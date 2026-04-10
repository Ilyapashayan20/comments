# Comments

<img src="art/preview.png" alt="Comments System" width="800">

A full-featured commenting system for Filament panels with threaded replies, @mentions, emoji reactions, and real-time updates.

[![Latest Version](https://img.shields.io/packagist/v/relaticle/comments.svg?style=for-the-badge)](https://packagist.org/packages/relaticle/comments)
[![Total Downloads](https://img.shields.io/packagist/dt/relaticle/comments.svg?style=for-the-badge)](https://packagist.org/packages/relaticle/comments)
[![PHP 8.2+](https://img.shields.io/badge/php-8.2%2B-blue.svg?style=for-the-badge)](https://php.net)
[![Laravel 12+](https://img.shields.io/badge/laravel-12%2B-red.svg?style=for-the-badge)](https://laravel.com)
[![Tests](https://img.shields.io/github/actions/workflow/status/relaticle/comments/tests.yml?branch=1.x&style=for-the-badge&label=tests)](https://github.com/relaticle/comments/actions)


## Features

- **Threaded Replies** - Nested comment threads with configurable depth limits
- **@Mentions** - Autocomplete user mentions with customizable resolver
- **Emoji Reactions** - 6 built-in reactions with configurable emoji sets
- **File Attachments** - Image and document uploads with validation
- **Notifications & Subscriptions** - Database and mail notifications with auto-subscribe
- **3 Filament Integrations** - Slide-over action, table action, and infolist entry


## Requirements

- **PHP:** 8.2+
- **Laravel:** 12+
- **Livewire:** 3.5+ / 4.x
- **Filament:** 4.x / 5.x


## Installation

```bash
composer require relaticle/comments
```

Publish and run migrations:

```bash
php artisan vendor:publish --tag=comments-migrations
php artisan migrate
```

## Usage

### Set Up Your Models

Add the commenting traits to your models:

```php
use Relaticle\Comments\Concerns\HasComments;
use Relaticle\Comments\Contracts\Commentable;

class Project extends Model implements Commentable
{
    use HasComments;
}
```

Add the commenter trait to your User model:

```php
use Relaticle\Comments\Concerns\CanComment;
use Relaticle\Comments\Contracts\Commentator;

class User extends Authenticatable implements Commentator
{
    use CanComment;
}
```

### Register the Filament Plugin

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

### Add Comments to Your Resources

Use the slide-over action on view/edit pages:

```php
use Relaticle\Comments\Filament\Actions\CommentsAction;

protected function getHeaderActions(): array
{
    return [
        CommentsAction::make(),
    ];
}
```

Or add as a table action:

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

Or embed in an infolist:

```php
use Relaticle\Comments\Filament\Infolists\Components\CommentsEntry;

public static function infolist(Infolist $infolist): Infolist
{
    return $infolist->schema([
        CommentsEntry::make('comments'),
    ]);
}
```

**[View Complete Documentation ->](https://relaticle.github.io/comments/)**

## Multi-tenancy

This package has built-in support for multi-tenant applications. When enabled, every comment query is automatically scoped to the current tenant, and every new comment is stamped with the current tenant's ID.

### How it works

The package adds a `tenant_id` column (nullable, no foreign key) to all five database tables. A Laravel global scope — `TenantScope` — adds `WHERE tenant_id = ?` to every `Comment` query automatically, including those triggered through the `HasComments` trait. The `CommentPolicy` independently verifies tenant ownership on `update` and `delete`.

### Step 1 — Publish and run migrations

If you have already run the package migrations, you will need to add the `tenant_id` column manually via a new migration in your application:

```php
Schema::table('comments', function (Blueprint $table) {
    $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('id');
});
// Repeat for: comment_attachments, comment_mentions, comment_reactions, comment_subscriptions
```

New installations will get the column automatically from the published stubs.

### Step 2 — Enable the feature in config

Publish the config file if you haven't already:

```bash
php artisan vendor:publish --tag="comments-config"
```

Then set `enabled` to `true` in `config/comments.php`:

```php
'multi_tenancy' => [
    'enabled' => true,

    // Change this if your tenant column has a different name (e.g. team_id, org_id)
    'tenant_column' => 'tenant_id',

    // Leave null here — register the resolver in a service provider instead (see below)
    'tenant_resolver' => null,
],
```

### Step 3 — Register a tenant resolver

The package does not know how your application determines the current tenant. You tell it by registering a closure that returns the current tenant's primary key (an `int`, a `string`, or `null`).

Do this in a service provider — `AppServiceProvider::boot()` works well:

```php
use Relaticle\Comments\CommentsConfig;

public function boot(): void
{
    CommentsConfig::resolveTenantUsing(function (): int|string|null {
        // Return the current tenant's ID, or null if there is no active tenant.
        // Returning null causes the scope to be skipped (safe for CLI / queue workers).
        return auth()->user()?->team_id;
    });
}
```

### Example — Filament multi-tenancy

Filament stores the active tenant in a singleton. Retrieve it with `Filament::getTenant()`:

```php
use Filament\Facades\Filament;
use Relaticle\Comments\CommentsConfig;

public function boot(): void
{
    CommentsConfig::resolveTenantUsing(fn () => Filament::getTenant()?->getKey());
}
```

`Filament::getTenant()` returns `null` outside of a Filament panel request (e.g. during `php artisan` commands), so the scope is automatically skipped in those contexts — no special handling needed.

### Example — Spatie Laravel-Multitenancy

[Spatie Laravel-Multitenancy](https://spatie.be/docs/laravel-multitenancy) stores the current tenant in a static property on the `Tenant` model:

```php
use Spatie\Multitenancy\Models\Tenant;
use Relaticle\Comments\CommentsConfig;

public function boot(): void
{
    CommentsConfig::resolveTenantUsing(fn () => Tenant::current()?->getKey());
}
```

`Tenant::current()` returns `null` when no tenant is active (landlord context, CLI), so the scope and policy tenant check are both skipped automatically.

### Bypassing the scope when needed

If you need to query across all tenants (e.g. in an admin panel or a console command), use `withoutGlobalScope`:

```php
use Relaticle\Comments\Scopes\TenantScope;

Comment::withoutGlobalScope(TenantScope::class)->where('body', 'like', '%spam%')->delete();
```

## Our Ecosystem

<table>
<tr>
<td width="50%" valign="top">

### FilaForms
[<img src="https://filaforms.app/img/og-image.png" width="100%" />](https://filaforms.app/)

Visual form builder for all your public-facing forms.
[Learn more ->](https://filaforms.app)

</td>
<td width="50%" valign="top">

### Custom Fields
[<img src="https://github.com/Relaticle/custom-fields/raw/3.x/art/preview.png" width="100%" />](https://relaticle.github.io/custom-fields)

Let users add custom fields to any model without code changes.
[Learn more ->](https://relaticle.github.io/custom-fields)

</td>
</tr>
</table>

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

MIT License. See [LICENSE](LICENSE) for details.
