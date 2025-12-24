<?php

declare(strict_types=1);

namespace Gems\Snippets\Messenger;

use Gems\Config\ConfigAccessor;
use Gems\Db\ResultFetcher;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\MetaModelLoader;
use Gems\Repository\PeriodSelectRepository;
use Gems\Snippets\AutosearchPeriodFormSnippet;
use Gems\Util\Translated;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

class MessageListSearchSnippet extends AutosearchPeriodFormSnippet
{
    protected readonly array $messengerConfig;
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        ConfigAccessor $configAccessor,
        MenuSnippetHelper $menuSnippetHelper,
        MetaModelLoader $metaModelLoader,
        ResultFetcher $resultFetcher,
        StatusMessengerInterface $messenger,
        PeriodSelectRepository $periodSelectRepository,
        protected readonly Translated $translatedUtil,
        array $config,
    ) {
        parent::__construct(
            $snippetOptions,
            $requestInfo,
            $translate,
            $configAccessor,
            $menuSnippetHelper,
            $metaModelLoader,
            $resultFetcher,
            $messenger,
            $periodSelectRepository
        );

        $this->messengerConfig = $config['messenger'] ?? [];
    }

    protected function getAutoSearchElements(array $data): array
    {
        $elements = parent::getAutoSearchElements($data);

        $empty = $this->translatedUtil->getEmptyDropdownArray();

        $queueOptions = $empty + $this->getQueueNames();

        $elements['queue'] = $this->_createSelectElement('queue_name',
            $queueOptions, $this->_('(Queue)')
        );

        $this->addPeriodSelectors($elements, ['available_at' => $this->_('Available between:')]);

        return $elements;
    }

    protected function getQueueNames(): array
    {
        $transports = $this->messengerConfig['transports'] ?? [];

        $queueNames = [];
        foreach($transports as $transportId => $transport) {
            if (!isset($transport['dsn']) || !str_starts_with($transport['dsn'], 'doctrine')) {
                continue;
            }
            $queueName = $transport['name'] ?? $transportId;

            $queueNames[$queueName] = $queueName;
        }

        return $queueNames;
    }
}