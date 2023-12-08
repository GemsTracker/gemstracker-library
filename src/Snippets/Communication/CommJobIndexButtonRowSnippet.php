<?php

namespace Gems\Snippets\Communication;

use Gems\Menu\MenuSnippetHelper;
use Gems\Menu\RouteHelper;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Util\Lock\CommJobLock;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessageTrait;
use Zalt\Message\MessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

class CommJobIndexButtonRowSnippet extends CurrentButtonRowSnippet
{
    use MessageTrait;

    protected $lockRoute  = 'setup.communication.job.lock';

    protected $monitorRoute  = 'setup.communication.job.monitor';

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MenuSnippetHelper $menuHelper,
        MessengerInterface $messenger,
        protected readonly CommJobLock $communicationJobLock,
        protected readonly RouteHelper $routeHelper,
    ) {
        $this->messenger = $messenger;

        parent::__construct($snippetOptions, $requestInfo, $translate, $menuHelper);
    }

    protected function getButtons(): array
    {
        if ($this->communicationJobLock->isLocked()) {
            $this->addMessage(sprintf(
                $this->_('Automatic messaging have been turned off since %s.'),
                $this->communicationJobLock->getLockTime()->format('H:i d-m-Y')
            ));

            // Set here because otherwise the button has already been rendered
            $commLockLabel = $this->_('Turn Autmatic Messaging Jobs ON');
        } else {
            $commLockLabel = $this->_('Turn Autmatic Messaging Jobs OFF');
        }

        if ($this->routeHelper->hasAccessToRoute($this->monitorRoute)) {
            $this->extraRoutesLabelled['setup.communication.job.monitor'] = $this->_('Monitor');
        }

        if ($this->routeHelper->hasAccessToRoute($this->lockRoute)) {
            $this->extraRoutesLabelled['setup.communication.job.lock'] = $commLockLabel;
        }

        return parent::getButtons();
    }
}