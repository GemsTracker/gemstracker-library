<?php

namespace Gems\AuthNew;

use Gems\User\User;
use Laminas\Db\Adapter\Adapter;

class TotpTfa implements TfaAdapterInterface
{
    public function __construct(
        private readonly User $user,
        private readonly string $passcode,
    ) {
    }

    private function makeResult(int $code, array $messages = []): TfaResult
    {
        return new TfaResult(TfaAdapterType::Totp, $code, $this->user, $messages);
    }

    public function authenticate(): TfaResult
    {
        if (!$this->user->hasTwoFactor()) {
            return $this->makeResult(TfaResult::FAILURE);
        }

        if (!$this->user->getTwoFactorAuthenticator()->verify($this->user->getTwoFactorKey(), $this->passcode)) {
            return $this->makeResult(TfaResult::FAILURE);
        }

        return $this->makeResult(TfaResult::SUCCESS);
    }
}
