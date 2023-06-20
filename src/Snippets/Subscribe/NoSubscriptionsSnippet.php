<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Subscribe
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Subscribe;

use Gems\Menu\RouteHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;
use Zalt\Snippets\MessageableSnippetAbstract;

/**
 * Snippet that is shown when there are no organizations to subscribe to.
 *
 * @package    Gems
 * @subpackage Snippets\Subscribe
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 19-Mar-2019 12:08:54
 */
class NoSubscriptionsSnippet extends MessageableSnippetAbstract
{
    /**
     * @var \Gems\Menu\RouteHelper
     */
    protected $routeHelper;

    public function __construct(SnippetOptions $snippetOptions,
                                RequestInfo $requestInfo,
                                TranslatorInterface $translate,
                                MessengerInterface $messenger,
                                RouteHelper $routeHelper)
    {
        $this->routeHelper = $routeHelper;

        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger);
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput()
    {
        $this->addMessage($this->_('Subscription not possible'));

        $html = $this->getHtmlSequence();

        $html->h2($this->_('No public subscriptions available'));
        $html->pInfo($this->_('Unfortunately no public subscriptions are available for this project.'));

        if ($this->routeHelper->hasAccessToRoute('contact.index')) {
            $html->pInfo($this->_('Please use our contact page if you want to participate.'));
            $html->actionLink($this->routeHelper->getRouteUrl('contact.index'), $this->_('Contact'));
        }

        return $html;
    }
}
