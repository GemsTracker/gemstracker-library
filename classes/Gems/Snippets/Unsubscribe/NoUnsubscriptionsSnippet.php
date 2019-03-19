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

/**
 *
 * @package    Gems
 * @subpackage Snippets\Unsubscribe
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 19-Mar-2019 12:18:39
 */
class NoUnsubscriptionsSnippet extends \MUtil_Snippets_SnippetAbstract
{
    /**
     *
     * @var \Gems_Menu
     */
    protected $menu;

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $this->addMessage($this->_('Unsubscribing not possible'));

        $html = $this->getHtmlSequence();
        $html->h2($this->_('Unsubscribing not possible'));
        $p = $html->pInfo($this->_('To unsubscribe please contact the organization that subscribed you to this project.'));

        $menu = $this->menu->findAllowedController('contact');
        if ($menu) {
            $p->append(' ');
            $p->append($this->_('The participating organizations are on the contact page.'));

            $html->append($menu->toActionLink());
        }



        return $html;
    }
}
