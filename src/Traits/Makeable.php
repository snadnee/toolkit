<?php

namespace Snadnee\Toolkit\Traits;

trait Makeable
{
    /**
     * Create a new instance.
     */
    public static function make(...$parameters): static
    {
        return app(static::class, $parameters);
    }
}
