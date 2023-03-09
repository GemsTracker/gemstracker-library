<?php

namespace Gems\Factory;

use Gems\Communication\CommunicationRepository;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

class MailTransportFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): TransportInterface
    {
        /**
         * @var $communicationRepository CommunicationRepository
         */
        $communicationRepository = $container->get(CommunicationRepository::class);
        return $communicationRepository->getCombinedTransport();
    }
}