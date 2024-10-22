<?php

namespace Snadnee\Toolkit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Snadnee\Toolkit\Toolkit
 */
class Toolkit extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Snadnee\Toolkit\Toolkit::class;
    }
}
