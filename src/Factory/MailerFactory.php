<?php

declare(strict_types=1);


namespace Gems\Factory;

use Gems\Helper\Env;
use Gems\Mail\MailBouncer;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

class MailerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): MailerInterface
    {
        $config = $container->get('config');
        $transport = $this->getTransport($config);

        // Load the MailBouncer to enable it
        $container->get(MailBouncer::class);

        $mailer = new Mailer($transport);

        return $mailer;
    }

    protected function getTransport(array $config): TransportInterface
    {
        $dsn = Env::get('MAILER_DSN',$config['mail']['dsn'] ?? null);
        if ($dsn) {
            return Transport::fromDsn($dsn);
        }

        return new SendmailTransport();
    }
}
