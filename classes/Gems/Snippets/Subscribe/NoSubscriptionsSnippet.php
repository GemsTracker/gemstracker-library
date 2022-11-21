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

/**
 *
 * @package    Gems
 * @subpackage Snippets\Subscribe
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 19-Mar-2019 12:08:54
 */
class NoSubscriptionsSnippet extends \MUtil\Snippets\SnippetAbstract
{
    /**
     *
     * @var \Gems\Menu
     */
    protected $menu;

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view = null)
    {
        $this->addMessage($this->_('Subscription not possible'));

        $html = $this->getHtmlSequence();
        $html->h2($this->_('No public subscriptions available'));
        $p = $html->pInfo($this->_('Unfortunately no public subscriptions are available for this project.'));

        $menu = $this->menu->findAllowedController('contact');
        if ($menu) {
            $p->append(' ');
            $p->append($this->_('Please use our contact page if you want to participate.'));

            $html->append($menu->toActionLink());
        }



        return $html;
    }
}
