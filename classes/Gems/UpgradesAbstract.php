<?php

/**
 * @package    Gems
 * @subpackage Upgrades
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

use MUtil\Translate\TranslateableTrait;

/**
 * This class can take care of handling upgrades that can not be achieved by a
 * simple db patch. For example adding an extra attribute to all token tables
 * in LimeSurvey needs a simple loop.
 *
 * @package    Gems
 * @subpackage Upgrades
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_UpgradesAbstract extends \Gems_Loader_TargetLoaderAbstract
{
    use TranslateableTrait;
    
    protected $_context = null;

    protected $_upgradeStack = array();

    protected $_messages = array();

    /**
     * Holds the inital config file
     *
     * @var \Zend_Config
     */
    protected $originalFile;

    /**
     * Holds the config file specific to this environment
     *
     * @var \Zend_Config
     */
    protected $upgradeFile;

    /**
     * @var \Gems_Task_TaskRunnerBatch
     */
    protected $_batch;

    /**
     * @var \Zend_Config_Ini
     */
    protected $_info;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * @var \Gems_Loader
     */
    public $loader;

    /**
     * @var \Gems_Project_ProjectSettings
     */
    public $project;

    public function __construct()
    {
        $this->upgradeFile = GEMS_ROOT_DIR . str_replace('/', DIRECTORY_SEPARATOR , '/var/settings/upgrades_' . APPLICATION_ENV . '.ini');
    }

    /**
     * Add a message to the stack
     *
     * @param string $message
     */
    protected function addMessage($message)
    {
        $this->_batch->addMessage($message);
    }
    
    public function afterRegistry()
    {
        parent::afterRegistry();
        
        if(!file_exists($this->upgradeFile)) {
            $this->initUpgradeFile();
        }
        $this->_info = new \Zend_Config_Ini($this->upgradeFile, null, array('allowModifications' => true));        
    }

     /**
     * Reset the message stack
     */
    protected function clearMessages()
    {
        $this->_messages = array();
    }

    /**
     * Execute upgrades for the given $context
     *
     * When no $to or $from are given, the given $context will be upgraded from the current level
     * to the max level. Otherwise the $from and/or $to will be used to determine what upgrades
     * to execute.
     *
     * @param string $context The context to execute the upgrades for
     * @param int|null $to The level to upgrade to
     * @param int|null $from The level to start the upgrade on
     * @return false|int The achieved upgrade level or false on failure
     */
    public function execute($context, $to = null, $from = null)
    {
        if(is_null($to)) {
            $to = $this->getMaxLevel($context);
        }
        if(is_null($from)) {
            $from = $this->getNextLevel($context);

            if ($from > $to) {
                $this->addMessage($this->_('Already at max. level.'));
                return $to;
            }
        }
        $from = max(1, intval($from));
        $to   = intval($to);

        $this->addMessage(sprintf($this->_('Trying upgrade for %s from level %s to level %s'), $context, $from, $to));

        $success = false;
        $upgrades = $this->_upgradeStack[$context];
        ksort($upgrades);
        $this->_upgradeStack[$context] = $upgrades;
        foreach($this->_upgradeStack[$context] as $level => $upgrade) {
            if (($level >= $from && $level <= $to))  {
                $this->addMessage(sprintf($this->_('Trying upgrade for %s to level %s: %s'), $context, $level, $this->_upgradeStack[$context][$level]['info']));
                if (call_user_func($upgrade['upgrade'])) {
                    $success = $level;
                } else {
                    $this->addMessage('FAILED');
                    break;
                }
            }
        }
        if ($success) {
            $this->setLevel($context, $success);
        }
        return $success;
    }

    /**
     * Retrieve the current context
     *
     * @return string
     */
    public function getContext() {
        return $this->_context;
    }

    /**
     * Get the current upgrade level for the given $context
     *
     * @param string $context
     * @return int
     */
    public function getLevel($context)
    {
        if(isset($this->_info->$context)) {
            return intval($this->_info->$context);
        } else {
            $level = $this->getMaxLevel($context);
            $this->setLevel($context, $level);
            return $level;
        }
    }

    /**
     * Get the highest level for the given $context
     *
     * @param string|null $context
     * @return int
     */
    public function getMaxLevel($context = null)
    {
        if (! $context) {
            $context = $this->getContext();
        }

        if (isset($this->_upgradeStack[$context])) {
            $values = array_keys($this->_upgradeStack[$context]);
            $values[] = 0;
            $index = intval(max($values));
            return $index;
        } else {
            return 0;

        }
    }

    /**
     * Get the next level for a given level and context
     *
     * When context is null, it will get the current context
     * When level is null, it will get the current level
     *
     * @param type $level
     * @param type $context
     * @return type
     */
    public function getNextLevel($context = null, $level = null) {
        if (is_null($context)) {
            $context = $this->getContext();
        }
        if (is_null($level)) {
            $level = $this->getLevel($context);
        }

        //Get all the levels
        $currentContext = $this->_upgradeStack[$context];
        ksort($currentContext);
        $levels = array_keys($currentContext);
        //Find the index of the current one
        $current = array_search($level, $levels);

        //And if it is present, return the next level
        if ($current !== false) {
            $current++;
            if (isset($levels[$current])) return $levels[$current];
        }

        //Else return current level +1 (doesn't exist anyway)
        return ++$level;
    }

    /**
     * Get all messages that were recorded during the upgrade process
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->_messages;
    }

    /**
     * Retrieve the upgrades for a certain context, will return an empty array when nothing present.
     *
     * @param string $context
     * @return array
     */
    public function getUpgrades($context = null) {
        if (! $context) {
                $context = $this->getContext();
            }

        if (isset($this->_upgradeStack[$context])) {
            return $this->_upgradeStack[$context];
        }
        return array();
    }

    /**
     * Retrieve info about the $requestedContext or all contexts when omitted
     *
     * @param string $requestedContext
     * @return array
     */
    public function getUpgradesInfo($requestedContext = null)
    {
        $result = array();
        foreach($this->_upgradeStack as $context => $content) {
            $row = array();
            $row['context'] = $context;
            $row['maxLevel'] =  $this->getMaxLevel($context);
            $row['level'] = $this->getLevel($context);
            $result[$context] = $row;
        }

        if (is_null($requestedContext)) {
            return $result;
        } else {
            if (isset($result[$requestedContext])) {
                return $result[$requestedContext];
            }
        }
    }
    
    /**
     * When upgrade file does not exist, create it and default to the max
     * level since no upgrades should be needed after a clean install
     */
    protected function initUpgradeFile()
    {
        touch($this->upgradeFile);
        $this->_info = new \Zend_Config_Ini($this->upgradeFile, null, array('allowModifications' => true));
        
        foreach($this->_upgradeStack as $context => $content) {
            $maxLevel = $this->getMaxLevel($context);
            $this->setLevel($context, $maxLevel, true);
        }
    }

    /**
     * Register an upgrade in the stack, it can be executed by using $this->execute
     *
     * Index and context are optional and will be generated when omitted. For the
     * user interface to be clear $info should provide a good description of what
     * the upgrade does.
     *
     * @param array|string $callback A valid callback, either string for a method of the current class or array otherwise
     * @param string $info A descriptive message about what this upgrade does
     * @param int $index The number of the upgrade
     * @param string $context The context to which this upgrade applies
     * @return boolean
     */
    public function register($callback, $info = null, $index = null, $context = null)
    {
        if (is_string($callback)) {
            $callback = array($this, $callback);
        }
        if (is_callable($callback)) {
            if (! $context) {
                $context = $this->getContext();
            }

            if (!isset($this->_upgradeStack[$context])) {
                $this->_upgradeStack[$context] = array();
            }

            if (is_null($index)) {
                $index = $this->getMaxLevel($context);
                $index++;
            }

            $this->_upgradeStack[$context][$index]['upgrade'] = $callback;
            $this->_upgradeStack[$context][$index]['info']    = $info;

            return true;
        }
        return false;
    }

    /**
     * Set the batch to use
     *
     * @param \Gems_Task_TaskRunnerBatch $batch
     */
    public function setBatch($batch)
    {
        $this->_batch = $batch;
    }

    /**
     * Change the active context
     *
     * Usefull when adding upgrades in the construct to save typing
     *
     * @param string $context
     */
    public function setContext($context) {
        $this->_context = $context;
    }

    /**
     * Set the upgrade level for the given $context to a certain level
     *
     * Will only update when the $level is higher than the achieved level, unless
     * when $force = true when it will always update.
     *
     * @param string $context
     * @param int $level
     * @param boolean $force
     */
    protected function setLevel($context, $level = null, $force = false)
    {
        if (!is_null($level) &&
            $this->_info->$context != $level &&
            ($force || $this->_info->$context < $level)) {
            $this->_info->$context = $level;
            $writer = new \Zend_Config_Writer_Ini();
            $writer->write($this->upgradeFile, $this->_info);
        }
    }
}