<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->morphs('user');
            $table->timestamp('created_at')->nullable();

            $table->unique(['commentable_type', 'commentable_id', 'user_type', 'user_id'], 'comment_subscriptions_unique');
        });
    }
};
