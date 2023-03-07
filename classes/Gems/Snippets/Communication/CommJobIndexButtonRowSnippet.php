<?php

namespace Gems\Snippets\Communication;

use Gems\MenuNew\MenuSnippetHelper;
use Gems\MenuNew\RouteHelper;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Util\Lock\CommJobLock;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\SnippetsLoader\SnippetOptions;

class CommJobIndexButtonRowSnippet extends CurrentButtonRowSnippet
{
    protected $lockRoute  = 'setup.communication.job.lock';
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MenuSnippetHelper $menuHelper,
        protected CommJobLock $commJobLock,
        protected RouteHelper $routeHelper,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $menuHelper);
    }

    protected function addButtons(): array
    {
        if (!$this->routeHelper->hasAccessToRoute($this->lockRoute)) {
            return parent::addButtons();
        }

        $url = $this->menuHelper->getRouteUrl('setup.communication.job.lock', []);
        $label = $this->commJobLock->isLocked() ? $this->_('Turn Automatic Messaging Jobs ON') : $this->_('Turn Automatic Messaging Jobs OFF');

        return [
            [
                'label' => $label,
                'url' => $url,
            ]
        ] + parent::addButtons();
    }
}