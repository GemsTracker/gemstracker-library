<?php

declare(strict_types=1);


/**
 * @package    Gems
 * @subpackage Snippets\Generic
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Generic;

/**
 * @package    Gems
 * @subpackage Snippets\Generic
 * @since      Class available since version 1.0
 */
trait ButtonRowTrait
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

    /**
     * @return array of label url arrays
     */
    protected function getButtons(): array
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
            var_dump($menuList);
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
        // file_put_contents('data/logs/echo.txt', get_class($this) . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($menuList, true) . "\n", FILE_APPEND);
        return $menuList;
    }

    public function getParentLabel(): string
    {
        return $this->parentLabel ?: $this->_('Cancel');
    }
}