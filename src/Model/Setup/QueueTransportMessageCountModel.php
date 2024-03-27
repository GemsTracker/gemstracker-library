<?php

namespace Gems\Model\Setup;

use Gems\Model\MetaModelLoader;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModel;
use Zalt\Model\Ra\ArrayModelAbstract;
use Zalt\Model\Ra\PhpArrayModel;

class QueueTransportMessageCountModel extends ArrayModelAbstract
{
    protected readonly array $messengerConfig;

    public function __construct(
        MetaModelLoader $metaModelLoader,
        array $config,
        protected readonly ContainerInterface $container,
        protected readonly TranslatorInterface $translator,
    ) {
        $metaModel = new MetaModel('queueTransportMessageCountModel', $metaModelLoader);
        parent::__construct($metaModel);

        $this->messengerConfig = $config['messenger'] ?? [];

        $this->metaModel->set('transportName', [
            'label' => $this->translator->_('Transport name'),
        ]);
        $this->metaModel->set('messageCount', [
            'label' => $this->translator->_('Message count'),
        ]);
    }

    protected function _loadAll(): array
    {
        return $this->getTransportCounts();
    }

    protected function getTransport(string $transportName): ?TransportInterface
    {
        if ($this->container->has($transportName)) {
            /**
             * @var TransportInterface
             */
            return $this->container->get($transportName);
        }
        return null;
    }

    protected function getTransportCounts(): array
    {
        $transportRows = [];
        $transportNames = array_keys($this->messengerConfig['transports'] ?? []);
        foreach($transportNames as $transportName) {
            $transport = $this->getTransport($transportName);
            $messageCount = null;
            if ($transport instanceof MessageCountAwareInterface) {
                $messageCount = $transport->getMessageCount();
            }

            $transportRow = [
                'transportName' => $transportName,
                'messageCount' => $messageCount,
            ];

            $transportRows[] = $transportRow;
        }

        return $transportRows;
    }
};