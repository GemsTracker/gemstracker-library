<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Unsubscribe
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Unsubscribe;

use Gems\Menu\RouteHelper;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;
use Zalt\Snippets\MessageableSnippetAbstract;

/**
 * Snippet that is shown when there are no organizations to unsubscribe from.
 *
 * @package    Gems
 * @subpackage Snippets\Unsubscribe
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 19-Mar-2019 12:18:39
 */
class NoUnsubscriptionsSnippet extends MessageableSnippetAbstract
{
    /*
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
        $this->addMessage($this->_('Unsubscribing not possible'));

        $html = $this->getHtmlSequence();
        $html->h2($this->_('Unsubscribing not possible'));
        $html->pInfo($this->_('To unsubscribe please contact the organization that subscribed you to this project.'));

        if ($this->routeHelper->hasAccessToRoute('contact.index')) {
            $html->pInfo($this->_('The participating organizations are on the contact page.'));
            $html->actionLink($this->routeHelper->getRouteUrl('contact.index'), $this->_('Contact'));
        }

        return $html;
    }
}
