<?php

namespace Gems\Task;

enum BatchStepOptions: string
{
    case FORM = 'form';
    case BATCH = 'batch';
    case RESET = 'reset';
}
