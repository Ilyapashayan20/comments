<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')
                ->constrained(config('comments.tables.comments', 'comments'))
                ->cascadeOnDelete();
            $table->morphs('user');
            $table->string('reaction');
            $table->timestamps();

            $table->unique(['comment_id', 'user_id', 'user_type', 'reaction']);
        });
    }
};
