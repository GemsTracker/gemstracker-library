<?php

namespace Gems\Messenger\Batch;

enum BatchStatus: string
{
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case RUNNING = 'running';

    case PENDING = 'pending';
}
