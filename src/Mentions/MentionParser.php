<?php

namespace Relaticle\Comments\Mentions;

use Illuminate\Support\Collection;
use Relaticle\Comments\CommentsConfig;
use Relaticle\Comments\Contracts\MentionResolver;
use Relaticle\Comments\Events\UserMentioned;
use Relaticle\Comments\Models\Comment;

class MentionParser
{
    public function __construct(
        protected MentionResolver $resolver,
    ) {}

    /** @return Collection<int, int> */
    public function parse(string $body): Collection
    {
        $text = html_entity_decode(strip_tags($body), ENT_QUOTES, 'UTF-8');

        preg_match_all('/(?<=@)[\w]+/', $text, $matches);

        $names = array_unique($matches[0] ?? []);

        if (empty($names)) {
            return collect();
        }

        return $this->resolver->resolveByNames($names)->pluck('id');
    }

    public function syncMentions(Comment $comment): void
    {
        $newMentionIds = $this->parse($comment->body);
        $existingMentionIds = $comment->mentions()->pluck('comment_mentions.commenter_id');

        $addedIds = $newMentionIds->diff($existingMentionIds);

        $comment->mentions()->sync($newMentionIds->all());

        $commenterModel = CommentsConfig::getCommenterModel();

        $addedIds->each(function ($userId) use ($comment, $commenterModel) {
            $mentionedUser = $commenterModel::find($userId);

            if ($mentionedUser) {
                UserMentioned::dispatch($comment, $mentionedUser);
            }
        });
    }
}
