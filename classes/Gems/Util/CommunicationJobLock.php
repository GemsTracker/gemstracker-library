<?php

namespace Gems\Util;

class CommunicationJobLock extends DatabaseLockAbstract
{
    protected string $key = 'communication-job-lock';
}