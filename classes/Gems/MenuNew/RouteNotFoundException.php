<?php

namespace Gems\MenuNew;

class RouteNotFoundException extends \LogicException
{
    public function __construct(string $routeName)
    {
        parent::__construct('Could not find route "' . $routeName . '"');
    }
}
