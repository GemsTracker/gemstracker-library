<?php

/**
 *
 * @package    Gems
 * @subpackage Menu
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Menu;

/**
 * A container item is one that gathers multiple sub menu
 * items, but does not have it's own controller/action pair
 * but selects the first sub item instead.
 *
 * @package    Gems
 * @subpackage Menu
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.5
 */
class ContainerItem extends \Gems\Menu\SubMenuItem
{
    /**
     *
     * @var \Zend_Session_Namespace
     */
    private static $_sessionStore;

    /**
     *
     * @return \Zend_Session_Namespace
     */
    private static function _getSessionStore($label)
    {
        if (! self::$_sessionStore instanceof \Zend_Session_Namespace) {
            self::$_sessionStore = new \Zend_Session_Namespace('MenuContainerItems');
        }
        if (! isset(self::$_sessionStore->$label)) {
            self::$_sessionStore->$label = new \ArrayObject();
        }

        return self::$_sessionStore->$label;
    }

    /**
     * Returns a \Zend_Navigation creation array for this menu item, with
     * sub menu items in 'pages'
     *
     * @param \Gems\Menu\ParameterCollector $source
     * @return array
     */
    protected function _toNavigationArray(\Gems\Menu\ParameterCollector $source)
    {
        $result = parent::_toNavigationArray($source);

        $store  = self::_getSessionStore($this->get('label'));
        if (isset($store->controller)) {
            foreach ($result['pages'] as $page) {
                if ($page['controller'] === $store->controller) {
                    if (isset($store->action) && $page['action'] === $store->action) {
                        $result['action'] = $store->action;
                        $this->set('action', $store->action);
                    }
                    $result['controller'] = $store->controller;
                    $this->set('controller', $store->controller);
                }
            }
        }

        // Get any missing MVC keys from children, even when invisible
        if ($requiredIndices = $this->notSet('controller', 'action')) {

            if (isset($result['pages'])) {
                $firstChild = null;
                $order = 0;
                foreach ($result['pages'] as $page) {
                    if ($page['allowed']) {
                        if ($page['order'] < $order || $order == 0) {
                            $firstChild = $page;
                            $order = $page['order'];
                        }
                    }
                }

                if (null === $firstChild) {
                    // No children are visible and required mvc properties
                    // are missing: ergo this page is not visible.
                    $result['visible'] = false;

                    // Use first (invisible) child as firstChild
                    $firstChild = reset($result['pages']);
                }
            } else {
                // Use '/' slash as default to ensure any not visible
                // menu items points to another existing item that is
                // active.
                $firstChild = array_fill_keys($requiredIndices, '/');
            }

            foreach ($requiredIndices as $key) {
                $result[$key] = $firstChild[$key];
            }
        }

        return $result;
    }

    /**
     * Set the visibility of the menu item and any sub items in accordance
     * with the specified user role.
     *
     * @param \Zend_Acl $acl
     * @param string $userRole
     * @return \Gems\Menu\MenuAbstract (continuation pattern)
     */
    protected function applyAcl(\MUtil\Acl $acl, $userRole)
    {
        parent::applyAcl($acl, $userRole);

        if ($this->isVisible()) {
            $this->set('allowed', false);
            $this->set('visible', false);

            if ($this->_subItems) {
                foreach ($this->_subItems as $item) {

                    if ($item->get('visible', true)) {
                        $this->set('allowed', true);
                        $this->set('visible', true);

                        return $this;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Make sure only the active branch is visible
     *
     * @param array $activeBranch Of \Gems\Menu_Menu Abstract items
     * @return \Gems\Menu\MenuAbstract (continuation pattern)
     */
    protected function setBranchVisible(array $activeBranch)
    {
        parent::setBranchVisible($activeBranch);

        $child  = end($activeBranch);
        $store  = self::_getSessionStore($this->get('label'));
        $contr  = $child->get('controller');
        $action = $child->get('action');

        $store->controller = $contr;
        $store->action = $action;
        $this->set('controller', $contr);
        $this->set('action', $action);

        return $this;
    }
}
