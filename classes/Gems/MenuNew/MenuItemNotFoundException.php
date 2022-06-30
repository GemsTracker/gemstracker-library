<?php

namespace Gems\MenuNew;

class MenuItemNotFoundException extends \LogicException
{
    public function __construct(string $routeName)
    {
        parent::__construct('Could not find route "' . $routeName . '" within menu structure');
    }
}
