<?php

namespace Gems\AuthTfa\Method;

use Gems\AuthTfa\Adapter\TotpAdapter;
use Gems\User\User;
use Symfony\Contracts\Translation\TranslatorInterface;

class AppTotp extends TotpAdapter implements OtpMethodInterface
{
    public function __construct(
        array $settings,
        private readonly TranslatorInterface $translator,
        User $user,
    ) {
        parent::__construct($settings, $user);
    }

    public function getCodeInputDescription(): string
    {
        return $this->translator->trans('From the TFA app on your phone.');
    }
}
