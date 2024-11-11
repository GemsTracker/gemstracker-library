<?php

namespace Gems\Command;

use Gems\Messenger\AddErrorDetailsStampListener;
use Gems\Messenger\FailureSenderContainer;
use Gems\Messenger\RetryStrategyContainer;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener;
use Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener;
use Symfony\Component\Messenger\RoutableMessageBus;

class ConsumeMessageCommandFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ConsumeMessagesCommand
    {
        $routableBus = $container->get(RoutableMessageBus::class);
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $container->get(EventDispatcherInterface::class);

        $eventDispatcher->addSubscriber(new AddErrorDetailsStampListener());

        $logger = null;
        if ($container->has('messageBusLogger')) {
            /**
             * @var LoggerInterface $logger
             */
            $logger = $container->get('messageBusLogger');
        }

        // Add Retry listener
        /**
         * @var RetryStrategyContainer $retryStrategyContainer
         */
        $retryStrategyContainer = $container->get(RetryStrategyContainer::class);
        $eventDispatcher->addSubscriber(new SendFailedMessageForRetryListener(
            $container,
            $retryStrategyContainer,
            $logger,
            $eventDispatcher,
        ));

        /**
         * @var FailureSenderContainer $failureSenderContainer
         */
        $failureSenderContainer = $container->get(FailureSenderContainer::class);
        $eventDispatcher->addSubscriber(new SendFailedMessageToFailureTransportListener(
            $failureSenderContainer,
            $logger,
        ));

        $config = $this->getMessengerConfig($container);
        $transportNames = array_keys($config['transports'] ?? []);
        $busNames = array_keys($config['buses'] ?? []);


        return new ConsumeMessagesCommand(
            $routableBus,
            $container,
            $eventDispatcher,
            $logger,
            $transportNames,
            null,
            $busNames,
        );
    }

    private function getMessengerConfig(ContainerInterface $container): array
    {
        $config = $container->get('config');
        return $config['messenger'] ?? [];
    }
}