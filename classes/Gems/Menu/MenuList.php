<?php

/**
 *
 * @package    Gems
 * @subpackage Menu
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Menu
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Menu_MenuList extends \MUtil_ArrayString implements \MUtil_Html_HtmlInterface
{
    const KEY_DISABLED = 'key_disabled';

    /**
     *
     * @var array of alternative label strings
     */
    protected $altLabels;

    /**
     *
     * @var string toActionLink|toActionLinkLower
     */
    protected $linkFunction = 'toActionLink';

    /**
     *
     * @var \Gems_Menu
     */
    protected $menu;

    /**
     * The first 'disabled' item sets the default value of
     *
     * @var array of Parameter source for drawing the menu items.
     */
    protected $sources = array(self::KEY_DISABLED => false);

    /**
     *
     * @param \Gems_Menu $menu
     * @param string $glue Optional, text to put between link items
     */
    public function __construct(\Gems_Menu $menu, $glue = ' ')
    {
        $this->menu = $menu;
        parent::__construct();

        $this->setGlue($glue);
    }

    /**
     * Generates the key name
     *
     * @param string $controller Controller name
     * @param string $action Action name
     * @return string
     */
    protected function _getKey($controller, $action)
    {
        return $controller . '.' . $action;
    }

    /**
     * Add a menu item by specifying the controller
     *
     * @param string $controller Controller name
     * @param string $action Action name
     * @param string $label Optional alternative label
     * @return \Gems_Menu_MenuList (continuation pattern)
     */
    public function addByController($controller, $action = 'index', $label = null)
    {
        $query['controller'] = $controller;
        $query['action']     = $action;

        if ($menuItem = $this->menu->findFirst($query)) {
            $this->addMenuItem($menuItem, $label);
        }

        return $this;
    }

    /**
     * Adds the children of the current menu item to this list
     *
     * @return \Gems_Menu_MenuList (continuation pattern)
     */
    public function addCurrentChildren()
    {
        foreach ($this->menu->getCurrentChildren() as $menuItem) {
            $this->addMenuItem($menuItem);
        }
        return $this;
    }

    /**
     * Adds the parent of parent of the current menu item
     *
     * Does nothing when the parent is a top level item (has no
     * controllor or is the \Gems_menu itself).
     *
     * @param string $label Optional alternative label
     * @return \Gems_Menu_MenuList (continuation pattern)
     */
    public function addCurrentGrandParent($label = null)
    {
        $parent = $this->menu->getCurrentParent();

        if ($parent && (! $parent->isTopLevel())) {
            $grandPa = $parent->getParent();
            if ($grandPa && (! $grandPa->isTopLevel())) {
                $this->addMenuItem($grandPa, $label);
            }
        }
        return $this;
    }

    /**
     * Adds the parent of the current menu item
     *
     * Does nothing when the parent is a top level item (has no
     * controllor or is the \Gems_menu itself).
     *
     * @param string $label Optional alternative label
     * @return \Gems_Menu_MenuList (continuation pattern)
     */
    public function addCurrentParent($label = null)
    {
        $parent = $this->menu->getCurrentParent();

        if ($parent && (! $parent->isTopLevel())) {
            $this->addMenuItem($parent, $label);
        }
        return $this;
    }

    /**
     * Adds the siblings (= other children of the parent) of the current menu item to this list
     *
     * @param boolean $anyParameters When false, siblings must have the same parameter set as the current menu item
     * @return \Gems_Menu_MenuList (continuation pattern)
     */
    public function addCurrentSiblings($anyParameters = false, $includeCurrent = false)
    {
        if ($current = $this->menu->getCurrent()) {
            if ($parent = $current->getParent()) {
                $parameters = $current->getParameters();
                if ($includeCurrent) {
                    $current = null;
                }

                foreach ($parent->getChildren() as $menuItem) {
                    // Add any menu item that is not the current menu item
                    // and that has the same parameters, unless $anyParameters is true.
                    if (($menuItem !== $current) && ($anyParameters || ($menuItem->getParameters() == $parameters))) {
                        $this->addMenuItem($menuItem);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Add a menu item to this list
     *
     * @param \Gems_Menu_SubMenuItem $menuItem
     * @param string $label Optional alternative label
     * @return \Gems_Menu_MenuList (continuation pattern)
     */
    public function addMenuItem(\Gems_Menu_SubMenuItem $menuItem, $label = null)
    {
        $key = $this->_getKey($menuItem->get('controller'), $menuItem->get('action'));

        if ($label) {
            $this->altLabels[$key] = $label;
        }

        $this->offsetSet($key, $menuItem);
        return $this;
    }

    /**
     *
     * @param mixed $source_array (Unlimited)
     * @return \Gems_Menu_MenuList (continuation pattern)
     */
    public function addParameterSources($source_1, $source_2 = null)
    {
        $args = func_get_args();

        $this->sources = array_merge($this->sources, $args);

        return $this;
    }

    /**
     * Get the action link for a specific item.
     *
     * @param string $controller Controller name
     * @param string $action Action name
     * @param boolean $remove Optional, set to true to remove the item from this list.
     * @return \MUtil_Html_HtmlElement
     */
    public function getActionLink($controller, $action = 'index', $remove = false)
    {
        $key = $this->_getKey($controller, $action);

        if ($this->offsetExists($key)) {

            $result = $this->toActionLink($key);

            if ($remove) {
                $this->offsetUnset($key);
            }

            return $result;
        }
    }

    /**
     * Get the action links for the specified items.
     *
     * @param boolean $remove Optional, set to true to remove the item from this list.
     * @param string $contr1 Controller name
     * @param string $action1 Action name, continues in pairs
     * @return \MUtil_Html_Sequence
     */
    public function getActionLinks($remove, $contr1, $action1 = null, $contr2 = null, $action2 = null)
    {
        $args    = func_get_args();
        $count   = func_num_args();
        $results = new \MUtil_Html_Sequence();
        $results->setGlue($this->getGlue());

        for ($i = 1; $i < $count; $i++) {
            if ($result = $this->getActionLink($args[$i], $args[++$i], $remove)) {
                $results->append($result);
            }
        }

        return $results;
    }

    /**
     * Get the action link for a specific item.
     *
     * @param boolean $remove Optional, set to true to remove the item from this list.
     * @return \MUtil_Html_HtmlElement
     */
    public function getFirstAction($remove = true)
    {
        foreach ($this->getArrayCopy() as $key => $item) {

            if ($result = $this->toActionLink($key)) {

                if ($remove) {
                    $this->offsetUnset($key);
                }

                return $result;
            }
        }
    }

    /**
     * Renders the element into a html string
     *
     * The $view is used to correctly encode and escape the output
     *
     * @param \Zend_View_Abstract $view
     * @return string Correctly encoded and escaped html output
     */
    public function render(\Zend_View_Abstract $view)
    {
        $html = '';
        $glue = $this->getGlue();

        foreach ($this->getIterator() as $key => $item) {
            $html .= $glue;

            if ($item instanceof \Gems_Menu_SubMenuItem) {
                $item = $this->toActionLink($key);
            }

            $html .= \MUtil_Html::renderAny($view, $item);
        }

        return substr($html, strlen($glue));
    }

    /**
     * Changes the label for a specific menu item
     *
     * @param string $controller Controller name
     * @param string $action Action name
     * @param string $label Alternative label
     * @return \Gems_Menu_MenuList (continuation pattern)
     */
    public function setLabel($controller, $action, $label)
    {
        $key = $this->_getKey($controller, $action);

        $this->altLabels[$key] = $label;

        return $this;
    }

    /**
     * Switches between lowercase links or normal case
     *
     * @param boolean $value
     * @return \Gems_Menu_MenuList (continuation pattern)
     */
    public function setLowerCase($value = true)
    {
        $this->linkFunction = $value ? 'toActionLink' : 'toActionLinkLower';
        return $this;
    }

    /**
     * Switches showing disabled menu items on or off (= default)
     *
     * @param boolean $value
     * @return \Gems_Menu_MenuList (continuation pattern)
     */
    public function showDisabled($value = true)
    {
        $this->sources[self::KEY_DISABLED] = (boolean) $value;
        return $this;
    }

    /**
     *
     * @param string $key
     * @return \MUtil_Html_HtmlElement
     */
    protected function toActionLink($key)
    {
        $sources = $this->sources;

        // Set an alternative label
        if (isset($this->altLabels[$key])) {
            $sources[] = $this->altLabels[$key];
        }

        // Call the toActionLink(Lower) function with the sources given.
        return call_user_func_array(array($this->offsetGet($key), $this->linkFunction), $sources);
    }
}
