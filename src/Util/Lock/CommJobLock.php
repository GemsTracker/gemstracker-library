<?php

namespace Gems\Util\Lock;

class CommJobLock extends VariableLockAbstract
{
    protected string $key = 'comm-job';
}