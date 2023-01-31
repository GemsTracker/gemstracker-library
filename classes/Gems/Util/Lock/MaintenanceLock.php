<?php

namespace Gems\Util\Lock;

class MaintenanceLock extends VariableLockAbstract
{
    protected string $key = 'maintenance-mode';
}