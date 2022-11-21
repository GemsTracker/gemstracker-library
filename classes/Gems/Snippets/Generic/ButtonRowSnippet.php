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
class ButtonRowSnippet extends \MUtil\Snippets\SnippetAbstract
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
     * @var RequestInfo
     */
    protected $requestInfo;

    /**
     * @var RouteHelper
     */
    protected $routeHelper;

    /**
     * @param array $menuList
     * @return array
     */
    protected function addButtons(array $menuList): array
    {
        $currentRoute = $this->requestInfo->getCurrentRouteResult();
        $currentRouteName = $currentRoute->getMatchedRouteName();
        $currentRouteParams = $currentRoute->getMatchedParams();
        if ($this->addCurrentParent) {
            $parent = $this->routeHelper->getRouteParent($currentRouteName);
            $params = $this->routeHelper->getRouteParamsFromKnownParams($parent, $currentRouteParams);
            $menuList[] = [
                'label' => $this->_('Cancel'),
                'url' => $this->routeHelper->getRouteUrl($parent['name'], $params),
            ];
        }
        if ($this->addCurrentSiblings) {
            // $menuList->addCurrentSiblings($this->anyParameterSiblings);
        }
        if ($this->addCurrentChildren) {
            // $menuList->addCurrentChildren();
        }
        // \MUtil\EchoOut\EchoOut::track($this->addCurrentParent, $this->addCurrentSiblings, $this->addCurrentChildren, count($menuList));
        return $menuList;
    }

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
        $menuList = [];

        $menuList = $this->addButtons($menuList);

        if (count($menuList)) {
            $container = \MUtil\Html::create('div', array('class' => 'buttons', 'renderClosingTag' => true), $menuList);
            foreach($menuList as $buttonInfo) {
                $container->append(\Gems\Html::actionLink($buttonInfo['url'], $buttonInfo['label']));
            }

            return $container;
        }
        return null;
    }
}
