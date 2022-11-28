<?php

namespace Nanhh\RAKE;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Nanhh\RAKE\Skeleton\SkeletonClass
 */
class RAKEFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'rake';
    }
}
