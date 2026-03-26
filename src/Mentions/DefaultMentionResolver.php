<?php

namespace Relaticle\Comments\Mentions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Relaticle\Comments\Config;
use Relaticle\Comments\Contracts\MentionResolver;

class DefaultMentionResolver implements MentionResolver
{
    /** @return Collection<int, Model> */
    public function search(string $query): Collection
    {
        $model = Config::getCommenterModel();

        return $model::query()
            ->where('name', 'like', "{$query}%")
            ->limit(Config::getMentionMaxResults())
            ->get();
    }

    /** @return Collection<int, Model> */
    public function resolveByNames(array $names): Collection
    {
        $model = Config::getCommenterModel();

        return $model::query()
            ->whereIn('name', $names)
            ->get();
    }
}
