<?php

namespace Gems\Log;

use Monolog\Logger;

class ErrorLogger extends Logger
{
    public function __construct() {
        parent::__construct('errorLogger');
    }
}