<?php

namespace Relaticle\Comments\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Relaticle\Comments\Concerns\IsCommenter;
use Relaticle\Comments\Contracts\Commenter;
use Relaticle\Comments\Tests\Database\Factories\UserFactory;

class User extends Authenticatable implements Commenter
{
    use HasFactory;
    use IsCommenter;
    use Notifiable;

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password'];

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
