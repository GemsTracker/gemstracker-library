<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Communication
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Communication;

use Gems\Menu\MenuSnippetHelper;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Util\Lock\CommJobLock;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Snippets\Communication
 * @since      Class available since version 1.0
 */
class CommLockSwitchSnippet extends \Zalt\Snippets\MessageableSnippetAbstract
{
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        protected readonly CommJobLock $communicationJobLock,
        protected readonly MenuSnippetHelper $menuSnippetHelper,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger);
    }

    public function getRedirectRoute(): ?string
    {
        return $this->menuSnippetHelper->getRouteUrl('setup.communication.job.index');
    }

    public function hasHtmlOutput(): bool
    {
        if ($this->communicationJobLock->isLocked()) {
            $this->communicationJobLock->unlock();
            $this->addMessage($this->_('Cron jobs are active'));
        } else {
            $this->communicationJobLock->lock();
            $this->addMessage($this->_('Cron jobs have been deactivated!'));
        }
        return false;
    }


}