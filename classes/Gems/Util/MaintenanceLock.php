<?php

namespace Gems\Util;

class MaintenanceLock extends DatabaseLockAbstract
{
    protected string $key = 'maintenance-mode';
}