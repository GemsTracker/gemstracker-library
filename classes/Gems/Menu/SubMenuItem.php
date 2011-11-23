<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Menu
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Sub menu items are all menu items except the main Menu item.
 *
 * Sub menu items "masks" three type of items:
 *  - plain simple menu items with an controller/action.
 *  - container items without controller/action taking
 *    those from their first active child.
 * - button items with a controller/action
 *
 * Menu items are displayed when:
 * - the current user has the correct privilige
 * - the parameters needed for display are given
 * - the parameter filter is true or show disabled is on
 *
 * The parameter values must be supplied when requesting to
 * draw a menu item. Valid sources for parameter values are:
 * - Gems_Menu_ParameterSourceInterface objects
 * - Zend_Controller_Request_Abstract objects
 * - MUtil_Lazy_RepeatableInterface objects
 * - array's
 *
 * Button items are only displayed through toActionLink()
 *
 * @see Gems_Menu
 * @see Gems_Menu_ParameterSourceInterface
 *
 * @package    Gems
 * @subpackage Menu
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Menu_SubMenuItem extends Gems_Menu_MenuAbstract
{
    private $_hiddenParameters;  // Added to $request by applyHiddenParameters
    private $_itemOptions;
    private $_parameters = true;
    private $_parameterFilter;
    private $_parent;
    private $_requiredParameters;

    public function __construct(GemsEscort $escort, Gems_Menu_MenuAbstract $parent, array $options)
    {
        parent::__construct($escort);

        $this->_parent = $parent;

        foreach ($options as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function __toString()
    {
        return print_r($this->_itemOptions, true);
    }

    private function _applyParameterFilter(Gems_Menu_ParameterCollector $source, $raiseConditions, &$condition)
    {
        if ($this->_parameterFilter) {
            foreach ($this->_parameterFilter as $name => $testValue) {
                $paramValue = $source->getMenuParameter($name);

                if ($paramValue instanceof MUtil_Lazy_LazyInterface) {
                    if ($raiseConditions) {
                        $paramValue = MUtil_Lazy::rise($paramValue);

                    } else {
                        $newCondition = MUtil_Lazy::comp($testValue, '==', $paramValue);
                        if ($condition instanceof MUtil_Lazy_LazyInterface) {
                            $condition = $condition->if($newCondition);
                        } else {
                            $condition = $newCondition;
                        }
                        continue;
                    }

                }
                if ($testValue !== $paramValue) {
                    if (Gems_Menu::$verbose) {
                        // Mutil_Echo::backtrace();
                        MUtil_Echo::r($name . ' => ' . $testValue . ' !== ' . $paramValue, $this->get('label') . ' (' . $this->get('controller') . '/' . $this->get('action') . ')');
                    }
                    return true;
                }
            }
        }
    }

    private function _applyParameterSource(Gems_Menu_ParameterCollector $source, array &$parameters)
    {
        // Fill in required parameters
        if ($this->_parameters && is_array($this->_parameters)) {
            foreach ($this->_parameters as $param => $name) {
                $parameters[$param] = $source->getMenuParameter($name, $param);
                // MUtil_Echo::r($param . '/' . $name . ' => ' . $value, $this->get('label'));
            }
        }

        return false;
    }

    private function _applyParameterSources(Gems_Menu_ParameterCollector $source, array &$parameters, $raiseConditions)
    {
        // Gems_Menu::$verbose = true;
        // MUtil_Echo::r($this->get('label'));
        $condition = true;

        if ($this->_applyParameterFilter($source, $raiseConditions, $condition)) {
            return false;
        }
        $this->_applyParameterSource($source, $parameters);

        // Test required parameters
        if ($this->_requiredParameters) {
            foreach ($this->_requiredParameters as $param => $name) {
                if (! isset($parameters[$param])) {
                    if (Gems_Menu::$verbose) {
                        // Mutil_Echo::backtrace();
                        MUtil_Echo::r('<b>Not found:</b> ' . $param . '/' . $name, $this->get('label') . ' (' . $this->get('controller') . '/' . $this->get('action') . ')');
                    }
                    return false;
                }
            }
        }
        return $condition;
    }

    private function _toHRef(Gems_Menu_ParameterCollector $source, &$condition)
    {
        if ($this->get('allowed')) {
            $parameters = array();

            if ($condition = $this->_applyParameterSources($source, $parameters, ! $condition)) {

                $url = new MUtil_Html_HrefArrayAttribute($parameters);
                $url->setRouteReset($this->get('reset_param', true));

                foreach (array('module', 'controller', 'action', 'route') as $name) {
                    if ($this->has($name)) {
                        $url->add($name, $this->get($name));
                        // MUtil_Echo::r($name . '-' . $this->get($name));
                    // } else {
                        // MUtil_Echo::r($name);
                    }
                }
                return $url;
            }
        }
    }

    private function _toLi(Gems_Menu_ParameterCollector $source)
    {
        $condition = false;
        if ($href = $this->_toHRef($source, $condition)) {
            $li = MUtil_Html::create()->li();

            $li->a($href, $this->get('label'));

            return $li;
        }
    }

    protected function _toNavigationArray(Gems_Menu_ParameterCollector $source)
    {
        $result = $this->_itemOptions;

        if ($result['visible']) {
            $parameters = array();
            if ($this->_applyParameterSources($source, $parameters, true)) {
                $result['params'] = $parameters;
            } else {
                $result['visible'] = false;
            }
        }

        if ($this->hasChildren()) {
            $result['pages'] = parent::_toNavigationArray($source);
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

    protected function _toRouteArray(Gems_Menu_ParameterCollector $source)
    {
        if ($this->get('allowed')) {
            $result = array();
            if ($this->_applyParameterSources($source, $result, true)) {
                if (isset($this->_itemOptions['controller'])) {
                    $result['controller'] = $this->_itemOptions['controller'];
                }
                if (isset($this->_itemOptions['action'])) {
                    $result['action'] = $this->_itemOptions['action'];
                }
                if (isset($this->_itemOptions['module'])) {
                    $result['module'] = $this->_itemOptions['module'];
                }

                // Get any missing MVC keys from children, even when invisible
                if ($requiredIndices = $this->notSet('controller', 'action')) {
                    $firstChild = null;

                    if ($this->hasChildren()) {
                        foreach ($this->getChildren() as $child) {
                            if ($child->check(array('allowed', true))) {
                                $firstChild = $firstChild->toRouteArray($source);
                                break;
                            }
                        }
                    }

                    if (null === $firstChild) {
                        // Route not possible
                        return null;
                    }

                    foreach ($requiredIndices as $key) {
                        $result[$key] = $firstChild[$key];
                    }
                }


                return $result;
            }
        }

        // Route not possible
        return null;
    }

    /**
     * Add an action to the current subMenuItem
     *
     * @param string $label         The label to display for the menu item
     * @param string $privilege     The privilege for the item
     * @param string $controller    What controller to use
     * @param string $action        The name of the action
     * @param array  $other         Array of extra options for this item, e.g. 'visible', 'allowed', 'class', 'icon', 'target', 'type', 'button_only'
     * @return Gems_Menu_SubMenuItem
     */
    public function addAction($label, $privilege = null, $action = 'index', array $other = array())
    {
        $other['label'] = $label;
        $other['controller'] = $this->get('controller');
        $other['action'] = $action;

        if (null === $privilege) {
            $privilege = $this->get('privilege');
        }

        if (null !== $privilege) {
            $other['privilege'] = $privilege;
        }

        return $this->add($other);
    }

    public function addActionButton($label, $privilege = null, $action = 'index', array $other = array())
    {
        $other['button_only'] = true;

        return $this->addAction($label, $privilege, $action, $other);
    }

    public function addAutofilterAction()
    {
        return $this->addAction(null, $this->get('privilege'), 'autofilter');
    }

    /**
     * Add an "Create new" action to the current subMenuItem
     *
     * @param string $privilege     The privilege for the item
     * @param array  $other         Array of extra options for this item, e.g. 'visible', 'allowed', 'class', 'icon', 'target', 'type', 'button_only'
     * @return Gems_Menu_SubMenuItem
     */
    public function addCreateAction($privilege = null, array $other = array())
    {
        if (isset($other['label'])) {
            $label = $other['label'];
            unset($other['label']);
        } else {
            $label = $this->_('New');
        }

        if (null === $privilege) {
            $privilege = $this->get('privilege') . '.create';
        }

        return $this->addAction($label, $privilege, 'create', $other);
    }

    public function addDeleteAction($privilege = null, array $other = array())
    {
        if (isset($other['label'])) {
            $label = $other['label'];
            unset($other['label']);
        } else {
            $label = $this->_('Delete');
        }

        if (null === $privilege) {
            $privilege = $this->get('privilege') . '.delete';
        }

        $menu = $this->addAction($label, $privilege, 'delete', $other);
        $menu->setModelParameters(1);

        return $menu;
    }

    public function addEditAction($privilege = null, array $other = array())
    {
        if (isset($other['label'])) {
            $label = $other['label'];
            unset($other['label']);
        } else {
            $label = $this->_('Edit');
        }

        if (null === $privilege) {
            $privilege = $this->get('privilege') . '.edit';
        }

        $menu = $this->addAction($label, $privilege, 'edit', $other);
        $menu->setModelParameters(1);

        return $menu;
    }

    public function addExcelAction()
    {
        $options = array(
            'class'  => 'excel',
            //'rel'    => 'external',
            'target' => null,
            'title'  => $this->_('Export the current data set to Excel'),
            //'type'   => 'application/vnd.ms-excel',
        );

        return $this->addActionButton($this->_('Excel export'), $this->get('privilege') . '.excel', 'excel', $options);
    }

    /**
     * Add parameter values that should not show in the url but that
     * must be added to the request when this menu item is current.
     *
     * @see applyHiddenParameters
     *
     * @param string $name Name of parameter
     * @param mixed $value
     * @return Gems_Menu_SubMenuItem (continuation pattern
     */
    public function addHiddenParameter($name, $value = null)
    {
        if (null === $value) {
            unset($this->_hiddenParameters[$name]);
        } else {
            $this->_hiddenParameters[$name] = $value;
        }

        return $this;
    }

    /**
     * Add required parameters - shown in the url - for this
     * menu item.
     *
     * Numeric array keys are changed into the same string as the
     * array value.
     *
     * @param mixed $arrayOrKey1 MUtil_Ra::pairs named array
     * @param mixed $key2
     * @return Gems_Menu_SubMenuItem (continuation pattern)
     */
    public function addNamedParameters($arrayOrKey1 = null, $altName1 = null)
    {
        $params = MUtil_Ra::pairs(func_get_args());

        if (true === $this->_parameters) {
            $this->_parameters = array();
        }
        foreach ($params as $param => $name) {
            if (is_int($param)) {
                $param = $name;
            }
            $this->_requiredParameters[$param] = $name;
            $this->_parameters[$param] = $name;
        }
        return $this;
    }

    public function addOptionalParameters($arrayOrKey1 = null, $altName1 = null)
    {
        $params = MUtil_Ra::pairs(func_get_args());

        if (true === $this->_parameters) {
            $this->_parameters = array();
        }
        foreach ($params as $param => $name) {
            if (is_int($param)) {
                $param = $name;
            }
            //$this->_requiredParameters[$param] = $name;
            $this->_parameters[$param] = $name;
        }
        return $this;
    }

    public function addParameters($arrayOrKey1 = null, $key2 = null)
    {
        $param = MUtil_Ra::args(func_get_args());

        $this->addNamedParameters($param);

        return $this;
    }

    public function addPdfButton($label, $privilege, $controller = null, $action = 'pdf', array $other = array())
    {
        static $pdfImg;

        if (null === $pdfImg) {
            $pdfImg = MUtil_Html::create()->img(array(
                'class'  => 'rightFloat',
                'src'    => 'pdf_small.gif',
                // 'width'  => 17,  // Removed as HCU layout uses smaller icon.
                // 'height' => 17,
                'alt'    => ''));
        }

        if (null === $controller) {
            $controller = $this->get('controller');
        }

        $other = $other + array(
            'button_only' => true,
            'class'       => 'pdf',
            'icon'        => $pdfImg,
            'target'      => '_blank',
            'type'        => 'application/pdf');

        return $this->addPage($label, $privilege, $controller, $action, $other);
    }

    public function addShowAction($privilege = null, array $other = array())
    {
        if (isset($other['label'])) {
            $label = $other['label'];
            unset($other['label']);
        } else {
            $label = $this->_('Show');
        }

        $menu = $this->addAction($label, $privilege, 'show', $other);
        $menu->setModelParameters(1);

        return $menu;
    }

    public function applyHiddenParameters(Zend_Controller_Request_Abstract $request, Gems_Menu_ParameterSource $source)
    {
        if ($this->_hiddenParameters) {
            foreach ($this->_hiddenParameters as $key => $value) {
                $request->setParam($key, $value);
                $source[$key] = $value;
            }
        }

        return $this;
    }

    public function applyToRequest(Zend_Controller_Request_Abstract $request)
    {
        $request->setActionName($this->get('action'));
        $request->setParam('action', $this->get('action'));
        $request->setControllerName($this->get('controller'));
        $request->setParam('controller', $this->get('controller'));
        $request->setModuleName($this->get('module'));

        // MUtil_Echo::r($request);

        return $this;
    }

    private function check(array $options)
    {
        foreach ($options as $key => $value) {
            if (! $this->is($key, $value)) {
                return false;
            }
        }

        return true;
    }

    /* private static function checkFilter(array $values, array $filter)
    {
        foreach ($filter as $key => $value) {
            if (isset($values[$key])) {
                if ($value != $values[$key]) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    } */

    /**
     * Check if a menu item contains these parameter settings
     *
     * @param mixed $arrayOrKey1 MUtil_Ra:pairs() name => value argument pairs
     * @param mixed $value1 The value should be identical or when null, should not exist or be null
     * @return boolean True if all values where set
     */
    public function checkParameterFilter($arrayOrKey1, $value1 = null)
    {
        $checks = MUtil_Ra::pairs(func_get_args());

        foreach($checks as $name => $value) {
            // MUtil_Echo::track($name, $value, $this->_parameterFilter[$name]);

            if (null === $value) {
                if (isset($this->_parameterFilter[$name])) {
                    return false;
                }
            } else {
                if (isset($this->_parameterFilter[$name])) {
                    if ($this->_parameterFilter[$name] != $value) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    protected function findItem($options, $findDeep = true)
    {
        if ((! $findDeep) && $this->check($options)) {
            return $this;
        }

        if ($item = parent::findItem($options)) {
            return $item;
        }

        if ($this->check($options)) {
            return $this;
        }
    }

    protected function findItemPath($options)
    {
        if ($path = parent::findItemPath($options)) {
            $path[] = $this;
            return $path;
        }

        if ($this->check($options)) {
            return array($this);
        }
    }

    protected function findItems($options, array &$results)
    {
        parent::findItems($options, $results);

        // MUtil_Echo::r($options);
        if ($this->check($options)) {
            $results[] = $this;
        }
    }

    public function get($key, $default = null)
    {
        if (isset($this->_itemOptions[$key])) {
            return $this->_itemOptions[$key];
        }
        return $default;
    }

    public function getParameters()
    {
        return $this->_requiredParameters;
    }

    /**
     *
     * @return Gems_Menu_MenuAbstract
     */
    public function getParent()
    {
        return $this->_parent;
    }

    /**
     *
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return isset($this->_itemOptions[$key]);
    }

    public function is($key, $value)
    {
        // MUtil_Echo::track($key, $value);
        $target = $this->get($key);

        if (is_array($value)) {
            foreach ($value as $val) {
                if ($target === $val) {
                    return true;
                }
            }
            return false;

        } else {
            return $target === $value;
        }
    }

    public function isTopLevel()
    {
        return ! $this->has('controller');
    }

    public function isVisible()
    {
        return $this->get('visible', true);
    }

    protected function notSet($key_args)
    {
        $resultKeys = array();

        foreach (func_get_args() as $key) {
            if (! isset($this->_itemOptions[$key])) {
                $resultKeys[] = $key;
            }
        }

        return $resultKeys;
    }

    public function removeParameters()
    {
        $this->_requiredParameters = null;
        return $this;
    }

    public function set($key, $value)
    {
        $this->_itemOptions[$key] = $value;
        return $this;
    }

    /**
     * Defines the number of named parameters using the model naming
     * convention: id=x or id1=x id2=y
     *
     * @see setNamedParamenters()
     *
     * @param int $idCount The number of parameters to define
     * @return Gems_Menu_SubMenuItem (continuation pattern)
     */
    public function setModelParameters($idCount)
    {
        $params = array();
        if (1 == $idCount) {
            $params[MUtil_Model::REQUEST_ID] = MUtil_Model::REQUEST_ID;
        } else {
            for ($i = 1; $i <= $idCount; $i++) {
                $params[MUtil_Model::REQUEST_ID . $i] = MUtil_Model::REQUEST_ID . $i;
            }
        }
        $this->setNamedParameters($params);

        return $this;
    }

    /**
     * Set the required parameters - shown in the url - for this
     * menu item.
     *
     * Numeric array keys are changed into the same string as the
     * array value.
     *
     * @param mixed $arrayOrKey1 MUtil_Ra::pairs named array
     * @param mixed $key2
     * @return Gems_Menu_SubMenuItem (continuation pattern)
     */
    public function setNamedParameters($arrayOrKey1 = null, $key2 = null)
    {
        $params = MUtil_Ra::pairs(func_get_args());

        $this->removeParameters();
        $this->addNamedParameters($params);

        return $this;
    }

    public function setParameterFilter($arrayOrKey1 = null, $value1 = null)
    {
        $filter = MUtil_Ra::pairs(func_get_args());

        $this->_parameterFilter = $filter;

        return $this;
    }

    /**
     *
     * @param mixed $parameterSources_args
     * @return MUtil_Html_AElement
     */
    public function toActionLink($parameterOrLabelSources_args = null)
    {
        if ($this->get('allowed')) {
            $parameterSources = func_get_args();

            $label = $this->get('label');
            $showDisabled = false;
            foreach ($parameterSources as $key => $source) {
                if (is_string($source) || ($source instanceof MUtil_Html_HtmlInterface)) {
                    $label = $source;
                    unset($parameterSources[$key]);
                }
                if (is_bool($source)) {
                    $showDisabled = $source;
                    unset($parameterSources[$key]);
                }
            }
            if ($this->has('icon')) {
                $label = array($this->get('icon'), $label);
            }

            $condition = true;
            if ($href = $this->_toHRef(new Gems_Menu_ParameterCollector($parameterSources), $condition)) {

                if ($condition instanceof MUtil_Lazy_LazyInterface) {
                    if ($showDisabled) {
                        // There is a (lazy) condition and disabled buttons should show
                        // so make the link an if
                        $element = MUtil_Html::create()->actionLink(MUtil_Lazy::iff($condition, $href), $label);

                        // and make the tagName an if
                        $element->tagName = MUtil_Lazy::iff($condition, 'a', 'span');
                    } else {
                        // There is a (lazy) condition and nothing should show when not there
                        // so make the label an if
                        $label = MUtil_Lazy::iff($condition, $label);
                        $element = MUtil_Html::create()->actionLink($href, $label);
                    }
                } else {
                    $element = MUtil_Html::create()->actionLink($href, $label);
                }

                // and make sure nothing shows when empty
                $element->setOnEmpty(null);
                $element->renderWithoutContent = false;

                foreach (array('onclick', 'rel', 'target', 'type') as $name) {
                    if ($this->has($name)) {
                        $value = $this->get($name);

                        if (isset($href[$value])) {
                            $value = $href[$value];
                        }

                        if (($condition instanceof MUtil_Lazy_LazyInterface) && $showDisabled) {
                            $element->$name = MUtil_Lazy::iff($condition, $value);
                        } else {
                            $element->$name = $value;
                        }
                    }
                }

            } elseif ($showDisabled) {
                // MUtil_Echo::r($label, 'No href');
                $element = MUtil_Html::create()->actionDisabled($label);

            } else {
                return;
            }

            if ($class = $this->get('class')) {
                $element->appendAttrib('class', $class);
            }
            return $element;
        }
    }

    /**
     *
     * @param mixed $parameterSources_args
     * @return MUtil_Html_AElement
     */
    public function toActionLinkLower($parameterSources_args = null)
    {
        $parameterSources = func_get_args();

        // Use unshift: if a label was specified it is now automatically used
        // as the last string is the label.
        array_unshift($parameterSources, strtolower($this->get('label')));

        return call_user_func_array(array($this, 'toActionLink'), $parameterSources);
    }

    /**
     *
     * @param mixed $parameterSources_args
     * @return MUtil_Html_HrefArrayAttribute
     */
    public function toHRefAttribute($parameterSources_args = null)
    {
        $parameterSources = func_get_args();

        $condition = true;
        return $this->_toHRef(new Gems_Menu_ParameterCollector($parameterSources), $condition);
    }

    public function toRouteUrl($parameterSources_args = null)
    {
        $parameterSources = func_get_args();

        return $this->_toRouteArray(new Gems_Menu_ParameterCollector($parameterSources));
    }

    public function toUl($actionController = null)
    {
        if ($this->isVisible() && $this->hasChildren()) {
            $parameterSources = func_get_args();

            $ul = MUtil_Html_ListElement::ul();

            foreach ($this->getChildren() as $menuItem) {
                if ($li = $menuItem->_toLi(new Gems_Menu_ParameterCollector($parameterSources))) {
                    $ul->append($li);
                }
            }

            if (count($ul)) {
                return $ul;
            }
        }
    }
}

