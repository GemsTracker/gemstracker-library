<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Generic
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Generic;

use Gems\Html;
use Gems\MenuNew\RouteHelper;
use MUtil\Request\RequestInfo;
use Zalt\Html\Routes\UrlRoutes;

/**
 * Displays the parent menu item (if existing) plus any current
 * level buttons that are visible
 *
 * @package    Gems
 * @subpackage Snippets\Generic
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2
 */
class ButtonRowSnippet extends \Zalt\Snippets\TranslatableSnippetAbstract
{
    /**
     * Add the children of the current menu item
     *
     * @var boolean
     */
    protected bool $addCurrentChildren = false;

    /**
     * Add the parent of the current menu item
     *
     * @var boolean
     */
    protected bool $addCurrentParent = false;

    /**
     * Add the siblings of the current menu item
     *
     * @var boolean
     */
    protected bool $addCurrentSiblings = false;

    /**
     * @return array
     */
    protected function getButtons(): array
    {
        $output = [];
        
        if ($this->addCurrentParent) {
            $parentUrl = UrlRoutes::getCurrentParentUrl();
            if ($parentUrl) {
                $output[$this->_('Cancel')] = $parentUrl;                
            }
        }
        if ($this->addCurrentSiblings) {
            // $menuList += UrlRoutes::getCurrentChildRoutes();
        }
        if ($this->addCurrentChildren) {
            $output += UrlRoutes::getCurrentChildRoutes();
        }
        // \MUtil\EchoOut\EchoOut::track($this->addCurrentParent, $this->addCurrentSiblings, $this->addCurrentChildren, count($menuList));
        file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($output, true) . "\n", FILE_APPEND);
        return $output;
    }

    public function getHtmlOutput(\Zend_View_Abstract $view = null)
    {
        $menuList = $this->getButtons();

        if (count($menuList)) {
            $container = Html::div(array('class' => 'buttons', 'renderClosingTag' => true));
            foreach($menuList as $label => $route) {
                $container->append(Html::actionLink($route, $label));
            }

            return $container;
        }
        return null;
    }
}
