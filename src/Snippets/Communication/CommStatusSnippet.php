<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Communication
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Communication;

use Gems\Menu\MenuSnippetHelper;
use Gems\Util\Lock\CommJobLock;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Html;
use Zalt\Message\MessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Snippets\Communication
 * @since      Class available since version 1.0
 */
class CommStatusSnippet extends \Zalt\Snippets\MessageableSnippetAbstract
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

    public function getHtmlOutput()
    {
        return Html::create('pInfo', $this->_('With automatic messaging jobs and a cron job on the server, messages can be sent without manual user action.'));
    }

    public function hasHtmlOutput(): bool
    {
        if ($this->communicationJobLock->isLocked()) {
            $this->addMessage(sprintf(
                $this->_('Automatic messaging have been turned off since %s.'),
                $this->communicationJobLock->getLockTime()->format('H:i d-m-Y')
            ));

            // Set here because otherwise the button has already been rendered
            $this->menuSnippetHelper->setMenuItemLabel('setup.communication.job.lock', $this->_('Turn Autmatic Messaging Jobs ON'));
        }

        return true;
    }
}