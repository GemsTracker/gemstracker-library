<?php

namespace Gems\Acl;

use Gems\Exception\AuthenticationException;

class Privilege
{
    public const SEPARATOR = '.';

    public function __construct(
        public readonly string $name,
        public readonly ?string $label = null,
        public readonly ?array $methods = null,
    )
    {}

    public function getName(?string $method = null): string
    {
        if ($method !== null && $this->methods !== null) {
            if (!in_array($method, $this->methods)) {
                throw new AuthenticationException('Method not allowed');
            }
            return $this->name . self::SEPARATOR . $method;
        }
        return $this->name;
    }

    public function getLabel(?string $method = null): string
    {
        if ($method !== null) {
            return $this->label . ' ' . $method;
        }
        if ($this->label !== null) {
            return $this->label;
        }
        return $this->getName($method);
    }
}