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
use Gems\MenuNew\MenuSnippetHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\SnippetsLoader\SnippetOptions;

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
     * @var array An array of routes
     */
    protected array $extraRoutes = [];

    /**
     * @var array An array of route => label
     */
    protected array $extraRoutesLabelled = [];
    
    /**
     * @var string|null 
     */
    protected ?string $parentLabel = null;  

    public function __construct(
        SnippetOptions              $snippetOptions,
        protected RequestInfo       $requestInfo,
        TranslatorInterface         $translate,
        protected MenuSnippetHelper $menuHelper)
    {
        parent::__construct($snippetOptions, $this->requestInfo, $translate);
    }

    /**
     * @param array $menuList
     * @return array
     */
    protected function addButtons() : array
    {
        $menuList = [];
        if ($this->addCurrentParent) {
            // $menuList += $this->menuHelper->getCurrentParentUrls();
            $menuList['parent'] = 
                [
                'label' => $this->getParentLabel(),
                'url'   => $this->menuHelper->getCurrentParentUrl(),
            ];
        }
        if ($this->addCurrentSiblings) {
            $menuList += $this->menuHelper->getCurrentSiblingUrls();
        }
        if ($this->addCurrentChildren) {
            $menuList += $this->menuHelper->getCurrentChildUrls();
        }
        if ($this->extraRoutes) {
            $menuList += $this->menuHelper->getRouteUrls($this->extraRoutes, $this->requestInfo->getParams());
        }
        if ($this->extraRoutesLabelled) {
            $params =$this->requestInfo->getParams();
            foreach ($this->extraRoutesLabelled as $route => $label) {
                $url = $this->menuHelper->getRouteUrl($route, $params);
                if ($url) {
                    $menuList[$route] = ['label' => $label, 'url' => $url];
                }
            }
        }
        // file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($menuList, true) . "\n", FILE_APPEND);
        return $menuList;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     */
    public function getHtmlOutput()
    {
        $menuList = $this->addButtons();

        if (count($menuList)) {
            $container = Html::create('div', array('class' => 'buttons', 'renderClosingTag' => true));
            foreach($menuList as $buttonInfo) {
                if (isset($buttonInfo['url'], $buttonInfo['label'])) {
                    $container->append(Html::actionLink($buttonInfo['url'], $buttonInfo['label']));
                }
            }

            return $container;
        }
        return null;
    }
    
    public function getParentLabel(): string
    {
        return $this->parentLabel ?: $this->_('Cancel');
    }
}
