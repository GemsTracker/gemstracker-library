<?php

namespace Gems\Communication\JobMessenger;


use Gems\Batch\BatchHandlerTrait;

abstract class JobMessengerAbstract implements JobMessengerInterface
{
    use BatchHandlerTrait;
}
