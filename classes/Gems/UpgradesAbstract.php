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
 * Short description of file
 *
 * @package    Gems
 * @subpackage Upgrades
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 215 2011-07-12 08:52:54Z michiel $
 */

/**
 * Short description for Upgrades
 *
 * Long description for class Upgrades (if any)...
 *
 * @package    Gems
 * @subpackage Upgrades
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_UpgradesAbstract extends Gems_Loader_TargetLoaderAbstract
{
    protected $_context = null;

    protected $_upgradeStack = array();

    protected $_messages = array();

    protected $upgradeFile;

    /**
     * @var Zend_Config_Ini
     */
    protected $_info;

    /**
     * @var Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * @var GemsEscort
     */
    public $escort;

    /**
     * @var Gems_Loader
     */
    public $loader;

    /**
     *
     * @var Gems_Util_DatabasePatcher
     */
    public $patcher;

    /**
     * @var Zend_Translate_Adapter
     */
    public $translate;

    public function __construct()
    {
        //First get a GemsEscort instance, as we might need that a lot (and it can not be injected)
        $this->escort = GemsEscort::getInstance();

        $this->upgradeFile = GEMS_ROOT_DIR . str_replace('/', DIRECTORY_SEPARATOR , '/var/settings/upgrades.ini');
        if(!file_exists($this->upgradeFile)) {
            touch($this->upgradeFile);
        }
        $this->_info = new Zend_Config_Ini($this->upgradeFile, null, array('allowModifications' => true));
    }

    /**
     * Proxy to the translate object
     *
     * @param string $messageId
     * @param type $locale
     * @return string
     */
    protected function _($messageId, $locale = null)
    {
        return $this->translate->_($messageId, $locale);
    }

    /**
     * Add a message to the stack
     *
     * @param string $message
     */
    protected function addMessage($message)
    {
        $this->_messages[] = $message;
    }

    /**
     * Now we have the requests answered, add the DatabasePatcher as it needs the db object
     *
     * @return boolean
     */
    public function checkRegistryRequestsAnswers() {
        //As an upgrade almost always includes executing db patches, make a DatabasePatcher object available
        $this->patcher = new Gems_Util_DatabasePatcher($this->db, 'patches.sql', $this->escort->getDatabasePaths());
        //No load all patches, and save the resulting changed patches for later (not used yet)
        $changed  = $this->patcher->uploadPatches($this->loader->getVersions()->getBuild());

        return true;
    }

    /**
     * Reset the message stack
     */
    protected function clearMessages()
    {
        $this->_messages = array();
    }

    public function execute($context, $to = null, $from = null)
    {
        if(is_null($to)) {
            $to = count($this->_upgradeStack[$context]);
        }
        if(is_null($from)) {
            $from = $this->getLevel($context);
        }
        $from = max(1, $from);

        $this->addMessage(sprintf($this->_('Trying upgrade for %s from level %s to level %s'), $context, $from, $to));

        $success = false;
        for($level = $from; $level<=$to; $level++) {
            if (isset($this->_upgradeStack[$context][$level]) && is_callable($this->_upgradeStack[$context][$level])) {
                $this->addMessage(sprintf($this->_('Trying upgrade for %s to level %s'), $context, $level));
                if (call_user_func($this->_upgradeStack[$context][$level])) {
                    $success = $level;
                    $this->addMessage('OK');
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

    public function getContext() {
        return $this->_context;
    }

    public function getLevel($context)
    {
        if(isset($this->_info->$context)) {
            return $this->_info->$context;
        } else {
            return 0;
        }
    }

    /**
     * Get the highest level for the given context
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

    public function getMessages()
    {
        return $this->_messages;
    }

    public function getUpgrades($requestedContext = null)
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

    public function register($callback, $index = null, $context = null)
    {
        if (is_string($callback)) {
            $callback = array($this, $callback);
        }
        if (is_callable($callback)) {
            if (! $context) {
                $context = $this->getContext();
            }

            if (isset($this->_upgradeStack[$context])) {
                $key = array_search($callback, $this->_upgradeStack[$context]);
                if ($key !== false) {
                    $index = $key;
                }
            } else {
                $this->_upgradeStack[$context] = array();
            }

            if (is_null($index)) {
                $index = $this->getMaxLevel($context);
                $index++;
            }

            $this->_upgradeStack[$context][$index] = $callback;

            return true;
        }
        return false;
    }

    public function setContext($context) {
        $this->_context = $context;
    }

    protected function setLevel($context, $level = null, $force = false)
    {
        if (!is_null($level) &&
            $this->_info->$context != $level &&
            ($force || $this->_info->$context < $level)) {
            $this->_info->$context = $level;
            $writer = new Zend_Config_Writer_Ini();
            $writer->write($this->upgradeFile, $this->_info);
        }
    }
}