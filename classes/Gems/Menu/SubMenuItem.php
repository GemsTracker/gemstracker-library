<?php

/**
 *
 * @package    Gems
 * @subpackage Menu
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
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
 * - \Gems_Menu_ParameterSourceInterface objects
 * - \Zend_Controller_Request_Abstract objects
 * - \MUtil_Lazy_RepeatableInterface objects
 * - array's
 *
 * Button items are only displayed through toActionLink()
 *
 * @see \Gems_Menu
 * @see \Gems_Menu_ParameterSourceInterface
 *
 * @package    Gems
 * @subpackage Menu
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Menu_SubMenuItem extends \Gems_Menu_MenuAbstract
{
    private $_hiddenOrgId;
    private $_hiddenParameters = array();  // Added to $request by applyHiddenParameters
    private $_itemOptions;
    private $_parameters = array();
    private $_parameterFilter = array();
    private $_parent;
    private $_requiredParameters = array();

    public function __construct(\Gems_Menu_MenuAbstract $parent, array $options)
    {
        parent::__construct();

        $this->_parent = $parent;

        $this->translate = $parent->translate;
        $this->translateAdapter = $parent->translateAdapter;

        $this->project = $parent->project;
        $this->currentUser = $parent->currentUser;
        $this->loader = $parent->loader;

        foreach ($options as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function __toString()
    {
        return print_r($this->_itemOptions, true);
    }

    /**
     * Return true when then $source does NOT contain all items in the parameterFilter
     *
     * @param \Gems_Menu_ParameterCollector $source
     * @param boolean $raiseConditions
     * @param mixed $condition
     * @return boolean
     */
    private function _applyParameterFilter(\Gems_Menu_ParameterCollector $source, $raiseConditions, &$condition)
    {
        foreach ($this->_parameterFilter as $name => $testValue) {
            $paramValue = $source->getMenuParameter($name);
            $testValue  = (array) $testValue;

            if ($paramValue instanceof \MUtil_Lazy_LazyInterface) {
                if (!$raiseConditions) {
                    $newCondition = new \MUtil_Lazy_Call('in_array', array($paramValue, $testValue));
                    if ($condition instanceof \MUtil_Lazy_LazyInterface) {
                        if ($condition instanceof \MUtil_Lazy_LazyAnd) {
                            $condition->add($newCondition);
                        } else {
                            $condition = new \MUtil_Lazy_LazyAnd($condition, $newCondition);
                        }
                    } else {
                        $condition = $newCondition;
                    }
                    continue;
                }
                $paramValue = \MUtil_Lazy::rise($paramValue);
            }

            if (!in_array($paramValue, $testValue)) {
                if (\Gems_Menu::$verbose) {
                    // Mutil_Echo::backtrace();
                    \MUtil_Echo::r($name . ' => ' . print_r($testValue,true) . ' !== ' . $paramValue, $this->get('label') . ' (' . $this->get('controller') . '/' . $this->get('action') . ')');
                }
                return true;
            }
        }

        return false;
    }

    private function _applyParameterSource(\Gems_Menu_ParameterCollector $source, array &$parameters)
    {
        // Fill in required parameters
        foreach ($this->_parameters as $param => $name) {
            $parameters[$param] = $source->getMenuParameter($name, $param);
            if (\Gems_Menu::$verbose) {
                \MUtil_Echo::r($param . '/' . $name . ' => ' . $parameters[$param], $this->get('label'));
            }
        }
    }

    /**
     * A function that determines the parameters that this menu item should have using these paramter
     * sources.
     *
     * @param \Gems_Menu_ParameterCollector $source A source of parameter values
     * @param array $parameters A usually empty array of parameters that is filled from the sources
     * @param boolean $raiseConditions When true, no lazyness is returned
     * @return boolean Or lazy condition. When true menu item is enabled otherwise false
     */
    private function _applyParameterSources(\Gems_Menu_ParameterCollector $source, array &$parameters, $raiseConditions)
    {
        // \Gems_Menu::$verbose = true;
        // \MUtil_Echo::r($this->get('label'));
        $condition = true;

        if ($this->_applyParameterFilter($source, $raiseConditions, $condition)) {
            return false;
        }
        $this->_applyParameterSource($source, $parameters);

        // Test required parameters
        foreach ($this->_requiredParameters as $param => $name) {
            if (! isset($parameters[$param])) {
                if (\Gems_Menu::$verbose) {
                    // \MUtil_Echo::backtrace();
                    \MUtil_Echo::r('<b>Not found:</b> ' . $param . '/' . $name, $this->get('label') . ' (' . $this->get('controller') . '/' . $this->get('action') . ')');
                }
                return false;
            }
        }

        if ($this->_hiddenOrgId && $raiseConditions) {
            // Remove org paramter that should remain hidden when conditions have been raised.
            if (isset($parameters[\MUtil_Model::REQUEST_ID1], $parameters[\MUtil_Model::REQUEST_ID2]) &&
                    ($parameters[\MUtil_Model::REQUEST_ID2] == $this->_hiddenOrgId)) {
                $parameters[\MUtil_Model::REQUEST_ID] = $parameters[\MUtil_Model::REQUEST_ID1];
                unset($parameters[\MUtil_Model::REQUEST_ID1], $parameters[\MUtil_Model::REQUEST_ID2]);
            }
        }

        return $condition;
    }

    /**
     * Generate a hrf attribute using these sources
     *
     * @param \Gems_Menu_ParameterCollector $source A parameter source collection
     * @param boolean $condition When true the system may create a Lazy condition for the url
     * @return \MUtil_Html_HrefArrayAttribute
     */
    private function _toHRef(\Gems_Menu_ParameterCollector $source, &$condition)
    {
        if ($this->get('allowed')) {
            $parameters = array();

            if ($condition = $this->_applyParameterSources($source, $parameters, ! $condition)) {

                if ($this->_hiddenOrgId) {
                    $url = new \Gems_Menu_HiddenOrganizationHrefAttribute($parameters);
                    $url->setHiddenOrgId($this->_hiddenOrgId);
                } else {
                    $url = new \MUtil_Html_HrefArrayAttribute($parameters);
                }
                $url->setRouteReset($this->get('reset_param', true));

                foreach (array('module', 'controller', 'action', 'route') as $name) {
                    if ($this->has($name)) {
                        $url->add($name, $this->get($name));
                        // \MUtil_Echo::r($name . '-' . $this->get($name));
                    // } else {
                        // \MUtil_Echo::r($name);
                    }
                }
                return $url;
            }
        }
    }

    private function _toLi(\Gems_Menu_ParameterCollector $source)
    {
        $condition = false;
        if ($href = $this->_toHRef($source, $condition)) {
            $li = \MUtil_Html::create()->li();

            $li->a($href, $this->get('label'));

            return $li;
        }
    }

    /**
     * Returns a \Zend_Navigation creation array for this menu item, with
     * sub menu items in 'pages'
     *
     * @param \Gems_Menu_ParameterCollector $source
     * @return array
     */
    protected function _toNavigationArray(\Gems_Menu_ParameterCollector $source)
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

        return $result;
    }

    protected function _toRouteArray(\Gems_Menu_ParameterCollector $source)
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
     * @param string $action        The name of the action
     * @param array  $other         Array of extra options for this item, e.g. 'visible', 'allowed', 'class', 'icon', 'target', 'type', 'button_only'
     * @return \Gems_Menu_SubMenuItem
     */
    public function addAction($label, $privilege = null, $action = 'index', array $other = array())
    {
        $other['label']      = $label;
        $other['controller'] = $this->get('controller');
        $other['action']     = $action;

        if (null === $privilege) {
            $privilege = $this->get('privilege');
        }

        if (null !== $privilege) {
            $other['privilege'] = $privilege;
        }

        return $this->add($other);
    }

    /**
     * Add a button only action to the current subMenuItem
     *
     * @param string $label         The label to display for the menu item
     * @param string $privilege     The privilege for the item
     * @param string $action        The name of the action
     * @param array  $other         Array of extra options for this item, e.g. 'visible', 'allowed', 'class', 'icon', 'target', 'type', 'button_only'
     * @return \Gems_Menu_SubMenuItem
     */
    public function addActionButton($label, $privilege = null, $action = 'index', array $other = array())
    {
        $other['button_only'] = true;

        return $this->addAction($label, $privilege, $action, $other);
    }

    /**
     * Add invisible autofilet action to the current subMenuItem
     *
     * @return \Gems_Menu_SubMenuItem
     */
    public function addAutofilterAction()
    {
        return $this->addAction(null, $this->get('privilege'), 'autofilter');
    }

    /**
     * Add an "Create new" action to the current subMenuItem
     *
     * @param string $privilege The privilege for the item, defaults to parent + .create when not specified
     * @param array  $other     Array of extra options for this item, e.g. 'visible', 'allowed', 'class', 'icon', 'target', 'type', 'button_only'
     * @return \Gems_Menu_SubMenuItem
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

    /**
     * Add a standard deactivate action and optional reactivate action to the current menu item
     *
     * @param string $checkField   The name of the field to filter on for deactivation
     * @param string $deactivateOn The value to check against for deactivation, no menu item when null
     * @param string $reactivateOn The value to check against for reactivation, no menu item when null
     * @param array  $otherDeact    Array of extra options for deactivate item, e.g. 'visible', 'allowed', 'class',
     *                             'icon', 'privilege', 'target', 'type', 'button_only'.
     * @param array  $otherReact    Array of extra options for reactivate item, e.g. 'visible', 'allowed', 'class',
     *                             'icon', 'privilege', 'target', 'type', 'button_only'.
     * @return \Gems_Menu_SubmenuItem[]
     */
    public function addDeReactivateAction($checkField, $deactivateOn = 1, $reactivateOn = 1, array $otherDeact = array(), array $otherReact = array())
    {
        $pages = array();

        if (null !== $deactivateOn) {
            if (isset($otherDeact['privilege'])) {
                $privilege = $otherDeact['privilege'];
            } else {
                $privilege = $this->get('privilege') . '.deactivate';
            }

            $deactivate = $this->addAction($this->_('Deactivate'), $privilege, 'deactivate', $otherDeact);
            $deactivate->setModelParameters(1)
                    ->addParameterFilter($checkField, $deactivateOn);
            $pages['deactivate'] = $deactivate;
        }

        if (null !== $reactivateOn) {
            if (isset($otherReact['privilege'])) {
                $privilege = $otherReact['privilege'];
            } else {
                $privilege = $this->get('privilege') . '.reactivate';
            }

            $reactivate = $this->addAction($this->_('Reactivate'), $privilege, 'reactivate', $otherReact);
            $reactivate->setModelParameters(1)
                    ->addParameterFilter($checkField, $reactivateOn);
            $pages['reactivate'] = $reactivate;
        }

        return $pages;
    }

    /**
     * Add a standard delete action to the current menu item
     *
     * @param string $privilege A privilege name, defaults to parent + .delete when not specified
     * @param array $other      Array of extra options for this item, e.g. 'visible', 'allowed', 'class', 'icon',
     *                          target', 'type', 'button_only'.
     * @return \Gems_Menu_SubmenuItem
     */
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

    /**
     * Add a standard edit action to the current menu item
     *
     * @param string $privilege A privilege name, defaults to parent + .edit when not specified
     * @param array $other Array of extra options for this item, e.g. 'visible', 'allowed', 'class', 'icon', 'target', 'type', 'button_only'
     * @return \Gems_Menu_SubmenuItem
     */
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

    /**
     * Add a standard edit action to the current menu item
     *
     * @return \Gems_Menu_SubmenuItem
     */
    public function addExportAction()
    {
        $options = array(
            'class'  => 'model-export',
            'target' => null,
            'title'  => $this->_('Export the current data set'),
        );

        return $this->addActionButton($this->_('Export'), $this->get('privilege') . '.export', 'export', $options);
    }

    /**
     * Add parameter values that should not show in the url but that
     * must be added to the request when this menu item is current.
     *
     * @see applyHiddenParameters
     *
     * @param string $name Name of parameter
     * @param mixed $value
     * @return \Gems_Menu_SubMenuItem (continuation pattern
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
     * Add a standard import action to the current menu item
     *
     * @param string $privilege A privilege name, defaults to parent + .import  when not specified
     * @param array $other Array of extra options for this item, e.g. 'visible', 'allowed', 'class', 'icon', 'target', 'type', 'button_only'
     * @return \Gems_Menu_SubmenuItem
     */
    public function addImportAction($privilege = null, array $other = array())
    {
        if (isset($other['label'])) {
            $label = $other['label'];
            unset($other['label']);
        } else {
            $label = $this->_('Import');
        }

        if (null === $privilege) {
            $privilege = $this->get('privilege') . '.import';
        }

        $menu = $this->addAction($label, $privilege, 'import', $other);

        return $menu;
    }

    /**
     * Add required parameters - shown in the url - for this
     * menu item.
     *
     * Numeric array keys are changed into the same string as the
     * array value.
     *
     * @param mixed $arrayOrKey1 \MUtil_Ra::pairs named array
     * @param mixed $key2
     * @return \Gems_Menu_SubMenuItem (continuation pattern)
     */
    public function addNamedParameters($arrayOrKey1 = null, $altName1 = null)
    {
        $params = \MUtil_Ra::pairs(func_get_args());

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
        $params = \MUtil_Ra::pairs(func_get_args());

        foreach ($params as $param => $name) {
            if (is_int($param)) {
                $param = $name;
            }
            //$this->_requiredParameters[$param] = $name;
            $this->_parameters[$param] = $name;
        }
        return $this;
    }

    public function addParameterFilter($arrayOrKey1 = null, $value1 = null)
    {
        $filter = \MUtil_Ra::pairs(func_get_args());

        $this->_parameterFilter = $filter + (array) $this->_parameterFilter;

        return $this;
    }

    public function addParameters($arrayOrKey1 = null, $key2 = null)
    {
        $param = \MUtil_Ra::args(func_get_args());

        $this->addNamedParameters($param);

        return $this;
    }

    public function addPdfButton($label, $privilege, $controller = null, $action = 'pdf', array $other = array())
    {
		/*
        static $pdfImg;

        if (null === $pdfImg) {
            $pdfImg = \MUtil_Html::create()->img(array(
                'class'  => 'rightFloat',
                'src'    => 'pdf_small.gif',
                // 'width'  => 17,  // Removed as HCU layout uses smaller icon.
                // 'height' => 17,
                'alt'    => ''));
        }
		// */

        if (null === $controller) {
            $controller = $this->get('controller');
        }

        $other = $other + array(
            'button_only' => true,
            'class'       => 'pdf',
            // 'icon'        => $pdfImg,
            'target'      => '_blank',
            'type'        => 'application/pdf');

        return $this->addPage($label, $privilege, $controller, $action, $other);
    }

    /**
     * Add a standard show action to the current menu item
     *
     * @param string $privilege A privilege name, defaults to parent + .show when not specified
     * @param array $other Array of extra options for this item, e.g. 'visible', 'allowed', 'class', 'icon', 'target', 'type', 'button_only'
     * @return \Gems_Menu_SubmenuItem
     */
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

    public function applyHiddenParameters(\Zend_Controller_Request_Abstract $request, \Gems_Menu_ParameterSource $source)
    {
        foreach ($this->_hiddenParameters as $key => $value) {
            $request->setParam($key, $value);
            $source[$key] = $value;
        }
        if ($this->_hiddenOrgId && $patientId = $request->getParam(\MUtil_Model::REQUEST_ID)) {
            $request->setParam(\MUtil_Model::REQUEST_ID1, $patientId);
            $request->setParam(\MUtil_Model::REQUEST_ID2, $this->_hiddenOrgId);
            $request->setParam(\MUtil_Model::REQUEST_ID,  null);
        }

        return $this;
    }

    public function applyToRequest(\Zend_Controller_Request_Abstract $request)
    {
        $request->setActionName($this->get('action'));
        $request->setParam('action', $this->get('action'));
        $request->setControllerName($this->get('controller'));
        $request->setParam('controller', $this->get('controller'));
        $request->setModuleName($this->get('module'));

        // \MUtil_Echo::r($request);

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
     * @param mixed $arrayOrKey1 \MUtil_Ra:pairs() name => value argument pairs
     * @param mixed $value1 The value should be identical or when null, should not exist or be null
     * @return boolean True if all values where set
     */
    public function checkParameterFilter($arrayOrKey1, $value1 = null)
    {
        $checks = \MUtil_Ra::pairs(func_get_args());

        foreach($checks as $name => $value) {
            // \MUtil_Echo::track($name, $value, $this->_parameterFilter[$name]);

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

        // \MUtil_Echo::r($options);
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
     * @return \Gems_Menu_MenuAbstract
     */
    public function getParent()
    {
        return $this->_parent;
    }

    /**
     * Get the privilege associated with this menu item
     *
     * @return string
     */
    public function getPrivilege()
    {
        return $this->get('privilege', null);
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
        // \MUtil_Echo::track($key, $value);
        $target = $this->get($key);

        if (is_array($value)) {
            // Strict check
            return in_array($target, $value, true);
        }

        return $target === $value;
    }

    /**
     * True when allowed
     *
     * @return boolean
     */
    public function isAllowed()
    {
        return $this->get('allowed', true);
    }

    public function isTopLevel()
    {
        return ! $this->has('controller');
    }

    /**
     * True when visible
     *
     * @return boolean
     */
    public function isVisible()
    {
        return $this->get('visible', true);
    }

    protected function notSet($keyArgs)
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
        $this->_parameters         = array();
        $this->_requiredParameters = array();
        return $this;
    }

    public function set($key, $value)
    {
        $this->_itemOptions[$key] = $value;
        return $this;
    }

    /**
     * Set the organization id of the org parameter that can remain hidden.
     *
     * @param type $orgId
     * @return \Gems_Menu_SubMenuItem (continuation pattern)
     */
    public function setHiddenOrgId($orgId)
    {
        $this->_hiddenOrgId = $orgId;
        return $this;
    }

    /**
     * Defines the number of named parameters using the model naming
     * convention: id=x or id1=x id2=y
     *
     * @see setNamedParamenters()
     *
     * @param int $idCount The number of parameters to define
     * @return \Gems_Menu_SubMenuItem (continuation pattern)
     */
    public function setModelParameters($idCount)
    {
        $params = array();
        if (1 == $idCount) {
            $params[\MUtil_Model::REQUEST_ID] = \MUtil_Model::REQUEST_ID;
        } else {
            for ($idx = 1; $idx <= $idCount; $idx++) {
                $params[\MUtil_Model::REQUEST_ID . $idx] = \MUtil_Model::REQUEST_ID . $idx;
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
     * @param mixed $arrayOrKey1 \MUtil_Ra::pairs named array
     * @param mixed $key2
     * @return \Gems_Menu_SubMenuItem (continuation pattern)
     */
    public function setNamedParameters($arrayOrKey1 = null, $key2 = null)
    {
        $params = \MUtil_Ra::pairs(func_get_args());

        $this->removeParameters();
        $this->addNamedParameters($params);

        return $this;
    }

    public function setParameterFilter($arrayOrKey1 = null, $value1 = null)
    {
        $filter = \MUtil_Ra::pairs(func_get_args());

        $this->_parameterFilter = $filter;

        return $this;
    }

    /**
     *
     * @param mixed $parameterSources_args
     * @return \MUtil_Html_AElement
     */
    public function toActionLink($parameterOrLabelSources_args = null)
    {
        if (!$this->get('allowed')) {
            return null;
        }

        $parameterSources = func_get_args();
        $label            = $this->get('label');
        $showDisabled     = false;
        foreach ($parameterSources as $key => $source) {
            if (is_string($source) || ($source instanceof \MUtil_Html_HtmlInterface)) {
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
        $element   = null;
        if ($href = $this->_toHRef(new \Gems_Menu_ParameterCollector($parameterSources), $condition)) {

            if ($condition instanceof \MUtil_Lazy_LazyInterface) {
                if ($showDisabled) {
                    // There is a (lazy) condition and disabled buttons should show
                    // so make the link an if
                    $element = \MUtil_Html::create()->actionLink(\MUtil_Lazy::iff($condition, $href), $label);

                    // and make the tagName an if
                    $element->tagName = \MUtil_Lazy::iff($condition, 'a', 'span');
                    $element->appendAttrib('class', \MUtil_Lazy::iff($condition, '', 'disabled'));
                } else {
                    // There is a (lazy) condition and nothing should show when not there
                    // so make the label an if
                    $label   = \MUtil_Lazy::iff($condition, $label);
                    $element = \MUtil_Html::create()->actionLink($href, $label);
                }
            } else {
                $element = \MUtil_Html::create()->actionLink($href, $label);
            }
            if ($title = $this->get('title')) {
                $element->title = $title;
            }

            // and make sure nothing shows when empty
            $element->setOnEmpty(null);
            $element->renderWithoutContent = false;

            foreach (array('onclick', 'rel', 'target', 'type') as $name) {
                if ($this->has($name)) {
                    $value = $this->get($name);

                    if (is_scalar($value) && isset($href[$value])) {
                        $value = $href[$value];
                    }

                    if (($condition instanceof \MUtil_Lazy_LazyInterface) && $showDisabled) {
                        $element->$name = \MUtil_Lazy::iff($condition, $value);
                    } else {
                        $element->$name = $value;
                    }
                }
            }

        } elseif ($showDisabled) {
            // \MUtil_Echo::r($label, 'No href');
            $element = \MUtil_Html::create()->actionDisabled($label);
        }

        if ($element && $class = $this->get('class')) {
            $element->appendAttrib('class', $class);
        }
        return $element;
    }

    /**
     *
     * @param mixed $parameterSourcesArgs
     * @return \MUtil_Html_AElement
     */
    public function toActionLinkLower($parameterSourcesArgs = null)
    {
        // return null;
        $parameterSources = func_get_args();

        // Use unshift: if a label was specified it is now automatically used
        // as the last string is the label.
        array_unshift($parameterSources, strtolower($this->get('label')));

        return call_user_func_array(array($this, 'toActionLink'), $parameterSources);
    }

    /**
     *
     * @param mixed $parameterSourcesArgs
     * @return \MUtil_Html_HrefArrayAttribute
     */
    public function toHRefAttribute($parameterSourcesArgs = null)
    {
        // return null;
        $parameterSources = func_get_args();

        $condition = true;
        return $this->_toHRef(new \Gems_Menu_ParameterCollector($parameterSources), $condition);
    }

    public function toRouteUrl($parameterSourcesArgs = null)
    {
        $parameterSources = func_get_args();

        return $this->_toRouteArray(new \Gems_Menu_ParameterCollector($parameterSources));
    }

    public function toUl($actionController = null)
    {
        if (!$this->isVisible() || !$this->hasChildren()) {
            return null;
        }

        $parameterSources = func_get_args();

        $ul = \MUtil_Html_ListElement::ul();

        foreach ($this->getChildren() as $menuItem) {
            if ($li = $menuItem->_toLi(new \Gems_Menu_ParameterCollector($parameterSources))) {
                $ul->append($li);
            }
        }

        if (count($ul)) {
            return $ul;
        }
    }
}
