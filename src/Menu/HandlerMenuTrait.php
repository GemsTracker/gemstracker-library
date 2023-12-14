<?php

declare(strict_types=1);


/**
 * @package    Gems
 * @subpackage Menu
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Menu;

use Zalt\Base\TranslateableTrait;

/**
 * @package    Gems
 * @subpackage Menu
 * @since      Class available since version 1.0
 */
trait HandlerMenuTrait
{
    use TranslateableTrait;

    /**
     * @param string $controllerClass The Handler class for the menu item
     * @param string $name The start name of the routes
     * @param string $label The label for the parent item
     * @param string[] $otherActions action => label for main menu label actions
     * @param string[] $otherShowActions action => label for show item menu label actions
     * @param string|int|null $position Optional position
     * @return array|string[]
     */
    protected function createMenuForHandler(string $controllerClass, string $name, string $label, array $otherActions = [], array $otherShowActions = [], string|int|null $position = null, string|null $parent = null)
    {
        $actions = $controllerClass::$actions;

        if (isset($actions['index'])) {
            $parent = $this->createMenuItem(name: $name . '.index', label: $label, position: $position, parent: $parent);
            unset($actions['index']);
        } else {
            $parent = $this->createMenuItem($name, $label, 'container', $position, $parent);
        }

        if (isset($actions['create'])) {
            $parent['children'][] = $this->createMenuItem($name . '.create', $this->_('Create'));
            unset($actions['create']);
        }
        if (isset($actions['show'])) {
            $show = $this->createMenuItem($name . '.show', $this->_('Show'));
            unset($actions['show']);

            if (isset($actions['edit'])) {
                $show['children'][] = $this->createMenuItem($name . '.edit', $this->_('Edit'));
                unset($actions['edit']);
            }
            if (isset($actions['delete'])) {
                $show['children'][] = $this->createMenuItem($name . '.delete', $this->_('Delete'));
                unset($actions['delete']);
            }

            foreach ($actions as $action => $actionClass) {
                if (isset($otherShowActions[$action])) {
                    $show['children'][] = $this->createMenuItem($name . '.' . $action, $otherShowActions[$action]);
                }
            }
            $parent['children'][] = $show;
        }
        if (isset($actions['export'])) {
            // At the moment not in the menu
            $parent['children'][] = $this->createMenuItem($name . '.export', $this->_('Export'));
            unset($actions['export']);
        }
        foreach ($actions as $action => $actionClass) {
            if (isset($otherActions[$action])) {
                $parent['children'][] = $this->createMenuItem($name . '.' . $action, $otherActions[$action]);
            }
        }

        return $parent;
    }

    public function createMenuItem(string $name, string $label, string $type = 'route-link-item', string|int|null $position = null, string|null $parent = null)
    {
        $item = [
            'name'  => $name,
            'label' => $label,
            'type'  => $type,
        ];

        if ($position !== null) {
            $item['position'] = $position;
        }
        if ($parent !== null) {
            $item['parent'] = $parent;
        }

        return $item;
    }
}