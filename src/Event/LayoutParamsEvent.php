<?php

declare(strict_types=1);

namespace Gems\Event;

use Symfony\Contracts\EventDispatcher\Event;

class LayoutParamsEvent extends Event
{
    public function __construct(
        private array $params,
    )
    {
    }

    public function addParam(string $name, mixed $value): void
    {
        $this->params[$name] = $value;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }
}