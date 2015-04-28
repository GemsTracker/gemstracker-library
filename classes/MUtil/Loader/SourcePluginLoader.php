<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * @package    MUtil
 * @subpackage Loader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: SourcePluginLoader .php 1748 2014-02-19 18:09:41Z matijsdejong $
 */

/**
 * Plugin loader that applies a source when loading
 *
 * @package    MUtil
 * @subpackage Loader
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class MUtil_Loader_SourcePluginLoader extends \MUtil_Loader_PluginLoader
{
    /**
     *
     * @var \MUtil_Registry_SourceInterface
     */
    protected $_source;

    /**
     * Show warning when source not set.
     *
     * @var boolean
     */
    public static $verbose = false;

    /**
     * Instantiate a new class using the arguments array for initiation
     *
     * @param string $className
     * @param array $arguments Instanciation arguments
     * @return className
     */
    public function createClass($className, array $arguments = array())
    {
        $object = parent::createClass($className, $arguments);
        if ($object instanceof \MUtil_Registry_TargetInterface) {
            if ($this->_source instanceof \MUtil_Registry_SourceInterface) {
                $this->_source->applySource($object);
            } elseif (self::$verbose) {
                \MUtil_Echo::r("Loading target class $className, but source not set.");
            }
        }

        return $object;
    }

    /**
     * Get the current source for the loader (if any)
     *
     * @return \MUtil_Registry_SourceInterface
     */
    public function getSource()
    {
        return $this->_source;
    }

    /**
     * Is there a source for the loader
     *
     * @return boolean
     */
    public function hasSource()
    {
        return $this->_source instanceof \MUtil_Registry_SourceInterface;
    }

    /**
     * Set the current source for the loader
     *
     * @param \MUtil_Registry_SourceInterface $source
     * @return \MUtil_Loader_SourcePluginLoader (continuation pattern)
     */
    public function setSource(\MUtil_Registry_SourceInterface $source)
    {
        $this->_source = $source;
        return $this;
    }
}
