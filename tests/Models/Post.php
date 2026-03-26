<?php

namespace Relaticle\Comments\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Relaticle\Comments\Concerns\HasComments;
use Relaticle\Comments\Contracts\Commentable;
use Relaticle\Comments\Tests\Database\Factories\PostFactory;

class Post extends Model implements Commentable
{
    use HasComments;
    use HasFactory;

    protected $table = 'posts';

    protected $fillable = ['title'];

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}
