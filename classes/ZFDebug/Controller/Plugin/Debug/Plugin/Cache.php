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
 * @version    $Id: Cache.php 345 2011-07-28 08:39:24Z 175780 $
 */

/**
 * @category   ZFDebug
 * @filesource
 * @package    ZFDebug_Controller
 * @subpackage Plugins
 * @copyright  Copyright (c) 2008-2009 ZF Debug Bar Team (http://code.google.com/p/zfdebug)
 * @license    http://code.google.com/p/zfdebug/wiki/License     New BSD License
 */
class ZFDebug_Controller_Plugin_Debug_Plugin_Cache implements ZFDebug_Controller_Plugin_Debug_Plugin_Interface
{
    /**
     * Contains plugin identifier name
     *
     * @var string
     */
    protected $_identifier = 'cache';

    /**
     * @var Zend_Cache_Backend_ExtendedInterface
     */
    protected $_cacheBackends = array();

    /**
     * Create ZFDebug_Controller_Plugin_Debug_Plugin_Cache
     *
     * @param array $options
     * @return void
     */
    public function __construct(array $options = array())
    {
        if (!isset($options['backend'])) {
            throw new Zend_Exception("ZFDebug: Cache plugin needs 'backend' parameter");
        }
        is_array($options['backend']) || $options['backend'] = array($options['backend']);
        foreach ($options['backend'] as $name => $backend) {
            if ($backend instanceof Zend_Cache_Backend_ExtendedInterface ) {
                $this->_cacheBackends[$name] = $backend;
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
        return 'Cache';
    }

    /**
     * Gets content panel for the Debugbar
     *
     * @return string
     */
    public function getPanel()
    {
        $panel = '';

        # Support for APC
        if (function_exists('apc_sma_info') && ini_get('apc.enabled')) {
            $mem = apc_sma_info();
            $mem_size = $mem['num_seg']*$mem['seg_size'];
            $mem_avail = $mem['avail_mem'];
            $mem_used = $mem_size-$mem_avail;
            
            $cache = apc_cache_info();
            
            $panel .= '<h4>APC '.phpversion('apc').' Enabled</h4>';
            $panel .= round($mem_avail/1024/1024, 1).'M available, '.round($mem_used/1024/1024, 1).'M used<br />'
                    . $cache['num_entries'].' Files cached ('.round($cache['mem_size']/1024/1024, 1).'M)<br />'
                    . $cache['num_hits'].' Hits ('.round($cache['num_hits'] * 100 / ($cache['num_hits']+$cache['num_misses']), 1).'%)<br />'
                    . $cache['expunges'].' Expunges (cache full count)'; 
        }

        foreach ($this->_cacheBackends as $name => $backend) {
            $fillingPercentage = $backend->getFillingPercentage();
            $ids = $backend->getIds();
            
            # Print full class name, backends might be custom
            $panel .= '<h4>Cache '.$name.' ('.get_class($backend).')</h4>';
            $panel .= count($ids).' Entr'.(count($ids)>1?'ies':'y').'<br />'
                    . 'Filling Percentage: '.$backend->getFillingPercentage().'%<br />';
            
            $cacheSize = 0;
            foreach ($ids as $id)
            {
                # Calculate valid cache size
                $mem_pre = memory_get_usage();
                if ($cached = $backend->load($id)) {
                    $mem_post = memory_get_usage();
                    $cacheSize += $mem_post-$mem_pre;
                    unset($cached);
                }                
            }
            $panel .= 'Valid Cache Size: '.round($cacheSize/1024, 1). 'K';
        }
        return $panel;
    }
}