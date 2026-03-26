<?php

namespace Relaticle\Comments\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface MentionResolver
{
    /** @return Collection<int, Model> */
    public function search(string $query): Collection;

    /** @return Collection<int, Model> */
    public function resolveByNames(array $names): Collection;
}
