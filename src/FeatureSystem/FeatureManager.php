<?php

namespace Relaticle\Comments\FeatureSystem;

use Relaticle\Comments\Enums\CommentsFeature;

class FeatureManager
{
    public static function isEnabled(CommentsFeature $feature): bool
    {
        $configurator = config('comments.features');

        if (! $configurator instanceof FeatureConfigurator) {
            return false;
        }

        return $configurator->isEnabled($feature);
    }
}
