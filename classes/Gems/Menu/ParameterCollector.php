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
 *
 * @package    Gems
 * @subpackage Menu
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Menu_ParameterCollector
{
    protected $sources = array();
    protected $values = array();

    public function __construct()
    {
        $sources = \MUtil_Ra::args(func_get_args());
        $array   = array();
        foreach ($sources as $key => $source) {
            // Fix for array sources.
            if (is_string($key)) {
                $array[$key] = $source;
            } else {
                $this->addSource($source);
            }
        }
        if ($array) {
            $this->addSource($array);
        }
    }

    public function addSource($source)
    {
        array_unshift($this->sources, $source);
    }

    /**
     * Returns a value to use as parameter for $name or
     * $default if this object does not contain the value.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getMenuParameter($name, $altname = null)
    {
        if (array_key_exists($name, $this->values) && ! empty($this->values[$name])) {
            return $this->values[$name];
        }

        $this->values[$name] = null;
        foreach ($this->sources as $source) {
            if ($source instanceof \MUtil_Model_Bridge_TableBridgeAbstract) {
                if ($source->has($name)) {
                    $this->values[$name] = $source->getLazy($name);
                }
            } elseif ($source instanceof \Gems_Menu_ParameterSourceInterface) {
                $this->values[$name] = $source->getMenuParameter($name, $this->values[$name]);

            } elseif ($source instanceof \Zend_Controller_Request_Abstract) {
                $value = $source->getParam($name, null);
                if (null === $value || empty($value)) {
                    $value = $source->getParam($altname, $this->values[$name]);
                }
                $this->values[$name] = $value;

            } elseif (is_array($source)) {
                // \MUtil_Echo::track($name, $source);
                if (isset($source[$name])) {
                    $this->values[$name] = $source[$name];
                }
            } elseif ($source instanceof \MUtil_Lazy_RepeatableInterface) {
                $this->values[$name] = $source->__get($name);

            }
            if (null !== $this->values[$name] && ! empty($this->values[$name])) {
                break;
            }
        }
        return $this->values[$name];
    }
}
