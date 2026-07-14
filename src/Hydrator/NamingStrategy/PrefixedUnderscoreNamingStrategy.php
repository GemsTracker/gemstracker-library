<?php

namespace Gems\Hydrator\NamingStrategy;

use Laminas\Hydrator\NamingStrategy\NamingStrategyInterface;
use Laminas\Hydrator\NamingStrategy\UnderscoreNamingStrategy;

class PrefixedUnderscoreNamingStrategy implements NamingStrategyInterface
{
    private UnderscoreNamingStrategy $alternative;

    public function __construct(
        protected string|null $prefix = null,
    )
    {
        $this->alternative = new UnderscoreNamingStrategy();
    }

    public function hydrate(string $name, ?array $data = null): string
    {
        if ($this->prefix && str_starts_with($name, $this->prefix)) {
            $name = substr($name, strlen($this->prefix));
        }
        // Should not occur, but it sometimes happens, so it's better to test
        if (! isset($this->alternative)) {
            $this->alternative = new UnderscoreNamingStrategy();
        }
        return $this->alternative->hydrate($name, $data);
    }

    public function extract(string $name, ?object $object = null): string
    {
        if ($this->prefix) {
            return $this->prefix . $this->alternative->extract($name, $object);
        }
        // Should not occur, but it sometimes happens, so it's better to test
        if (! isset($this->alternative)) {
            $this->alternative = new UnderscoreNamingStrategy();
        }
        return $this->alternative->extract($name, $object);
    }
}