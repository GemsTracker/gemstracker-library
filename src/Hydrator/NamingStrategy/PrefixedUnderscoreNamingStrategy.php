<?php

namespace Gems\Hydrator\NamingStrategy;

use Laminas\Hydrator\NamingStrategy\UnderscoreNamingStrategy;

class PrefixedUnderscoreNamingStrategy extends UnderscoreNamingStrategy
{
    public function __construct(
        protected string|null $prefix = null,
    )
    {
    }

    public function hydrate(string $name, ?array $data = null): string
    {
        if ($this->prefix && str_starts_with($name, $this->prefix)) {
            $name = substr($name, strlen($this->prefix));
        }
        return parent::hydrate($name, $data);
    }

    public function extract(string $name, ?object $object = null): string
    {
        if ($this->prefix) {
            return $this->prefix . parent::extract($name, $object);
        }
        return parent::extract($name, $object);
    }
}