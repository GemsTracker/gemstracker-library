<?php

/**
 *
 * @package    Gems
 * @subpackage Conditions
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems;

use MUtil\Translate\TranslateableTrait;

/**
 * Per project overruleable condition processing engine
 *
 * @package    Gems
 * @subpackage Conditions
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
class Conditions extends \Gems_Loader_TargetLoaderAbstract
{
    use TranslateableTrait;
    
    const ROUND_CONDITION = 'Round';

    /**
     * Each condition type must implement a condition class or interface derived
     * from ConditionInterface specified in this array.
     *
     * @see \Gems\Condition\ConditionInterface
     *
     * @var array containing eventType => eventClass for all condition classes
     */
    protected $_conditionClasses = array(
        self::ROUND_CONDITION => 'Gems\\Condition\\RoundConditionInterface',        
    );
    
    /**
     *
     * @var array containing conditionType => name for all condition classes
     */
    protected $_conditionTypes = array(
        self::ROUND_CONDITION => 'Round',
    );
    
    /**
     * Allows sub classes of \Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Condition';
    
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Lookup condition class for an event type. This class or interface should at the very least
     * implement the ConditionInterface.
     *
     * @see \Gems\Condition\ConditionInterface
     *
     * @param string $conditionType The type (i.e. lookup directory) to find the associated class for
     * @return string Class/interface name associated with the type
     */
    protected function _getConditionClass($conditionType)
    {
        if (isset($this->_conditionClasses[$conditionType])) {
            return $this->_conditionClasses[$conditionType];
        } else {
            throw new \Gems_Exception_Coding("No condition class exists for condition type '$conditionType'.");
        }
    }

    /**
     *
     * @param string $conditionType An event subdirectory (may contain multiple levels split by '/'
     * @return array An array of type prefix => classname
     */
    protected function _getConditionDirs($conditionType)
    {
        $eventClass = str_replace('/', '_', $conditionType);

        foreach ($this->_dirs as $name => $dir) {
            $prefix = $name . '_'. $eventClass . '_';
            $paths[$prefix] = $dir . DIRECTORY_SEPARATOR . $conditionType;
        }

        return $paths;
    }

    /**
     * Returns a list of selectable conditions with an empty element as the first option.
     *
     * @param string $conditionType The type (i.e. lookup directory with an associated class) of the conditions to list
     * @return \Gems\Condition\ConditionInterface or more specific a $conditionClass type object
     */
    protected function _listEvents($conditionType)
    {
        $results    = array();
        $conditionClass = $this->_getConditionClass($conditionType);
        $paths      = $this->_getConditionDirs($conditionType);

        foreach ($paths as $prefix => $path) {
            if (file_exists($path)) {
                $eDir = dir($path);
                $parts = explode('_', $prefix, 2);
                if ($name = reset($parts)) {
                    $name = ' (' . $name . ')';
                }

                while (false !== ($filename = $eDir->read())) {
                    if ('.php' === substr($filename, -4)) {
                        $conditionName = $prefix . substr($filename, 0, -4);

                        // Take care of double definitions
                        if (! isset($results[$conditionName])) {
                            $eventNsName = '\\' . strtr($conditionName, '_', '\\');
                            if (! (class_exists($conditionName, false) || class_exists($eventNsName, false))) {
                                include($path . '/' . $filename);
                            }

                            if ((! class_exists($conditionName, false)) && class_exists($eventNsName, false)) {
                                $conditionName = $eventNsName;
                            }
                            $event = new $conditionName();

                            if ($event instanceof $conditionClass) {
                                if ($event instanceof \MUtil_Registry_TargetInterface) {
                                    $this->applySource($event);
                                }

                                $results[$conditionName] = trim($event->getName()) . $name;
                            }
                            // \MUtil_Echo::track($eventName);
                        }
                    }
                }
            }
        }
        natcasesort($results);
        $results = $this->util->getTranslated()->getEmptyDropdownArray() + $results;
        // \MUtil_Echo::track($paths, $results);
        return $results;
    }

    /**
     * Loads and initiates a condition class and returns the class (without triggering the cvondition itself).
     *
     * @param string $conditionName The class name of the individual event to load
     * @param string $conditionType The type (i.e. lookup directory with an associated class) of the event
     * @return \Gems_tracker_TrackerEventInterface or more specific a $eventClass type object
     */
    protected function _loadEvent($conditionName, $conditionType)
    {
        $conditionClass = $this->_getConditionClass($conditionType);

        if (! class_exists($conditionName, true)) {
            throw new \Gems_Exception_Coding("The condition '$conditionName' of type '$conditionType' can not be found");
        }

        $condition = new $conditionName();

        if (! $condition instanceof $conditionClass) {
            throw new \Gems_Exception_Coding("The condition '$conditionName' of type '$conditionType' is not an instance of '$conditionClass'.");
        }

        if ($condition instanceof \MUtil_Registry_TargetInterface) {
            $this->applySource($condition);
        }

        return $condition;
    }
    
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->initTranslateable();
    }
    
    public function getConditionsFor($conditionType)
    {
        $model = $this->loader->getModels()->getCondtionModel();
        $filter = [
            'gcon_type' => $conditionType,
            'gcon_active' => 1
            ];
        $model->trackUsage();
        $model->set('gcon_id');
        $model->set('gcon_name');
        $conditions = $model->load($filter, ['gcon_name']);
        
        $output = $this->util->getTranslated()->getEmptyDropdownArray();
        
        foreach($conditions as $condition) {
            $output[$condition['gcon_id']] = $condition['gcon_name'];
        }
        
        return $output;
    }
    
    public function getConditionTypes()
    {
        return $this->_conditionTypes;
    }
    
    /**
     *
     * @return array eventname => string
     */
    public function listConditionsForType($conditionType)
    {
        return $this->_listEvents($conditionType);
    }

    /**
     *
     * @return array eventname => string
     */
    public function listRoundConditions()
    {
        return $this->_listEvents(self::ROUND_CONDITION);
    }
    
    /**
     *
     * @param string $conditionName
     * @return \Gems\Condition\RoundConditionInterface
     */
    public function loadConditionForType($conditionType, $conditionName)
    {
        return $this->_loadEvent($conditionName, $conditionType);
    }

    /**
     *
     * @param string $conditionName
     * @return \Gems\Condition\RoundConditionInterface
     */
    public function loadRoundCondition($conditionName)
    {
        return $this->_loadEvent($conditionName, self::ROUND_CONDITION);
    }
}