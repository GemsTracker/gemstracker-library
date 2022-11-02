<?php

namespace Gems\AuthNew;

use Gems\User\User;
use Mezzio\Session\SessionInterface;

class LoginStatusTracker
{
    private const TRACKER_DATA_SESSION_KEY = 'login_status_tracker';

    public function __construct(
        private readonly SessionInterface $session,
        private readonly User $user,
    ) {
    }

    public static function make(SessionInterface $session, User $user): self
    {
        return new self($session, $user);
    }

    private function all(): ?array
    {
        return $this->session->get(self::TRACKER_DATA_SESSION_KEY);
    }

    private function get(string $name, mixed $default = null): mixed
    {
        return $this->all()[$this->user->getLoginName()][$name] ?? $default;
    }

    private function set(string $name, mixed $value): void
    {
        $all = $this->all();

        // Scoping the data to the login name is not technically necessary,
        // but we include it as a safety precaution.
        $all[$this->user->getLoginName()][$name] = $value;

        $this->session->set(self::TRACKER_DATA_SESSION_KEY, $all);
    }

    public function clear(): void
    {
        $this->session->unset(self::TRACKER_DATA_SESSION_KEY);
    }

    public function isPasswordResetActive(): bool
    {
        return $this->get('passwordResetting', false);
    }

    public function setPasswordResetActive(bool $value = true): void
    {
        $this->set('passwordResetting', $value);
    }
}
