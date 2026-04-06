<?php

namespace Relaticle\Comments\FeatureSystem;

use Relaticle\Comments\Enums\CommentsFeature;

class FeatureConfigurator
{
    /** @var array<int, CommentsFeature> */
    private array $enabled = [];

    public static function configure(): static
    {
        return new static;
    }

    public function enable(CommentsFeature ...$features): static
    {
        foreach ($features as $feature) {
            if (! in_array($feature, $this->enabled, true)) {
                $this->enabled[] = $feature;
            }
        }

        return $this;
    }

    public function disable(CommentsFeature ...$features): static
    {
        $this->enabled = array_values(
            array_filter($this->enabled, fn ($f) => ! in_array($f, $features, true))
        );

        return $this;
    }

    public function isEnabled(CommentsFeature $feature): bool
    {
        return in_array($feature, $this->enabled, true);
    }
}
