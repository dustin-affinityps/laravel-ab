<?php

namespace DustinAP\AbTesting;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Dustinaffinityps\AbTesting\AbTesting
 */
class AbTestingFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'ab-testing';
    }
}
