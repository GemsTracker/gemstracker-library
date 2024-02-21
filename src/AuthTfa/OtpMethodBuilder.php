<?php

namespace Gems\AuthTfa;

use Gems\AuthTfa\Method\AuthenticatorTotp;
use Gems\AuthTfa\Method\MailHotp;
use Gems\AuthTfa\Method\OtpMethodInterface;
use Gems\AuthTfa\Method\SmsHotp;
use Gems\Cache\HelperAdapter;
use Gems\Communication\Http\SmsClientInterface;
use Gems\User\User;
use Gems\User\UserMailer;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;

class OtpMethodBuilder
{
    public function __construct(
        private readonly array $config,
        private readonly TranslatorInterface $translator,
        private readonly ContainerInterface $container,
        private readonly HelperAdapter $throttleCache,
    ) {
    }

    public function buildSpecificOtpMethod(string $className, User $user): OtpMethodInterface
    {
        $settings = $this->config['twofactor']['methods'][$className] ?? [];

        return match($className) {
            'AuthenticatorTotp' => new AuthenticatorTotp($settings, $this->translator, $user, $this->throttleCache, $this->config),
            'MailHotp' => new MailHotp($settings, $this->translator, $user, $this->throttleCache, $this->container->get(UserMailer::class)),
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

    public function buildOtpMethod(User $user): OtpMethodInterface
    {
        return $this->buildSpecificOtpMethod($user->getTfaMethodClass(), $user);
    }

    public function setOtpMethod(User $user, string $className): void
    {
        $user->setTwoFactorKey($className, $this->buildSpecificOtpMethod($className, $user)->generateSecret());
    }

    public function setOtpMethodAndSecret(User $user, string $className, string $secret): void
    {
        $user->setTwoFactorKey($className, $secret);
    }
}