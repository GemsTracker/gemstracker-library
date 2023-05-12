<?php

namespace Gems;

use Mezzio\Session\SessionInterface;

class SessionNamespace implements SessionInterface
{
    public function __construct(
        private readonly SessionInterface $session,
        private readonly string $namespace,
    ) {
    }

    public function toArray(): array
    {
        return $this->session->get($this->namespace, []);
    }

    public function get(string $name, $default = null)
    {
        return $this->toArray()[$name] ?? $default;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->toArray());
    }

    public function set(string $name, $value): void
    {
        $data = $this->toArray();

        $data[$name] = $value;

        $this->session->set($this->namespace, $data);
    }

    public function unset(string $name): void
    {
        $data = $this->toArray();

        unset($data[$name]);

        $this->session->set($this->namespace, $data);
    }

    public function clear(): void
    {
        $this->session->unset($this->namespace);
    }

    public function hasChanged(): bool
    {
        throw new \BadMethodCallException();
    }

    public function regenerate(): SessionInterface
    {
        throw new \BadMethodCallException();
    }

    public function isRegenerated(): bool
    {
        throw new \BadMethodCallException();
    }
}
