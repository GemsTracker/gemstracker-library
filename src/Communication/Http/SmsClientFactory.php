<?php

declare(strict_types=1);

namespace Gems\Communication\Http;

use Psr\Container\ContainerInterface;

class SmsClientFactory extends HttpClientFactory
{
    public function __construct()
    {
        parent::__construct('sms');
    }
}