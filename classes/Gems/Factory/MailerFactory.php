<?php

declare(strict_types=1);


namespace Gems\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\SendmailTransport;

class MailerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): MailerInterface
    {
        $config = $container->get('config');
        if (isset($config['mail'], $config['mail']['dsn'])) {
            $transport = Transport::fromDsn($config['mail']['dsn']);
        } else {
            $transport = new SendmailTransport();
        }
        $mailer = new Mailer($transport);

        return $mailer;
    }
}
