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
 */

/**
 * ZFDebug Zend Additions
 *
 * @category   ZFDebug
 * @filesource
 * @package    ZFDebug_Controller
 * @subpackage Plugins
 * @copyright  Copyright (c) 2008-2009 ZF Debug Bar Team (http://code.google.com/p/zfdebug)
 * @license    http://code.google.com/p/zfdebug/wiki/License     New BSD License
 * @version    $Id$
 */

/**
 * @see Zend_Db_Table_Abstract
 */

/**
 * @category   ZFDebug
 * @filesource
 * @package    ZFDebug_Controller
 * @subpackage Plugins
 * @copyright  Copyright (c) 2008-2009 ZF Debug Bar Team (http://code.google.com/p/zfdebug)
 * @license    http://code.google.com/p/zfdebug/wiki/License     New BSD License
 */
class ZFDebug_Controller_Plugin_Debug_Plugin_Database extends ZFDebug_Controller_Plugin_Debug_Plugin implements ZFDebug_Controller_Plugin_Debug_Plugin_Interface
{

    /**
     * Contains plugin identifier name
     *
     * @var string
     */
    protected $_identifier = 'database';

    /**
     * @var array
     */
    protected $_db = array();

    /**
     * Create ZFDebug_Controller_Plugin_Debug_Plugin_Variables
     *
     * @param Zend_Db_Adapter_Abstract|array $adapters
     * @return void
     */
    public function __construct(array $options = array())
    {
        if(!isset($options['adapter']) || !count($options['adapter'])) {
            if (Zend_Db_Table_Abstract::getDefaultAdapter()) {
                $this->_db[0] = Zend_Db_Table_Abstract::getDefaultAdapter();
                $this->_db[0]->getProfiler()->setEnabled(true);
            }
        } else if ($options['adapter'] instanceof Zend_Db_Adapter_Abstract ) {
            $this->_db[0] = $options['adapter'];
        	$this->_db[0]->getProfiler()->setEnabled(true);
        } else {
            foreach ($options['adapter'] as $name => $adapter) {
                if ($adapter instanceof Zend_Db_Adapter_Abstract) {
                    $adapter->getProfiler()->setEnabled(true);
                    $this->_db[$name] = $adapter;
                }
            }
        }
    }

    /**
     * Gets identifier for this plugin
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->_identifier;
    }

    /**
     * Gets menu tab for the Debugbar
     *
     * @return string
     */
    public function getTab()
    {
        if (!$this->_db)
            return 'No adapter';

        foreach ($this->_db as $adapter) {
            $profiler = $adapter->getProfiler();
            $adapterInfo[] = $profiler->getTotalNumQueries().' in '.round($profiler->getTotalElapsedSecs()*1000, 2).' ms';
        }
        $html = implode(' / ', $adapterInfo);

        return $html;
    }

    /**
     * Gets content panel for the Debugbar
     *
     * @return string
     */
    public function getPanel()
    {
        if (!$this->_db)
            return '';

        $html = '<h4>Database queries</h4>';
        if (Zend_Db_Table_Abstract::getDefaultMetadataCache ()) {
            $html .= 'Metadata cache is ENABLED';
        } else {
            $html .= 'Metadata cache is DISABLED';
        }

        foreach ($this->_db as $name => $adapter) {
            if ($profiles = $adapter->getProfiler()->getQueryProfiles()) {
                $html .= '<h4>Adapter '.$name.'</h4><ol>';
                foreach ($profiles as $profile) {
                    $html .= '<li><strong>['.round($profile->getElapsedSecs()*1000, 2).' ms]</strong> '
                             .htmlspecialchars($profile->getQuery()).htmlspecialchars(print_r($profile->getQueryParams(),true)).'</li>';
                }
                $html .= '</ol>';
            }
        }

        return $html;
    }

}