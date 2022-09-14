<?php

namespace Gems\AuthTfa;

use Gems\AuthTfa\Method\AppTotp;
use Gems\AuthTfa\Method\MailHotp;
use Gems\AuthTfa\Method\OtpMethodInterface;
use Gems\AuthTfa\Method\SmsHotp;
use Gems\Cache\HelperAdapter;
use Gems\Communication\Http\SmsClientInterface;
use Gems\User\User;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class OtpMethodBuilder
{
    public function __construct(
        private readonly array $config,
        private readonly TranslatorInterface $translator,
        private readonly ContainerInterface $container,
        private readonly HelperAdapter $throttleCache,
    ) {
    }

    public function buildOtpMethod(User $user): OtpMethodInterface
    {
        $className = $user->getTfaMethodClass();

        $settings = $this->config['twofactor']['methods'][$className] ?? [];

        return match($className) {
            'AppTotp' => new AppTotp($settings, $this->translator, $user, $this->throttleCache),
            'MailHotp' => new MailHotp($settings, $this->translator, $user, $this->throttleCache),
            'SmsHotp' => new SmsHotp(
                $settings,
                $this->translator,
                $user,
                $this->container->get(SmsClientInterface::class),
                $this->throttleCache
            ),
            default => throw new \Exception('Invalid TFA class value "' . $className . '"'),
        };
    }
}
