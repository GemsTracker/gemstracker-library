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
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Export extends \Gems_Loader_TargetLoaderAbstract
{
    /**
     * Allows sub classes of \Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Export';

    /**
     * Is set to the calling controller to allow rendering the view
     *
     * @var \Gems_Controller_Action
     */
    public $controller = null;

    /**
     * This variable holds all registered export classes, may be changed in derived classes
     *
     * @var array Of classname => description
     */
    protected $_exportClasses = array(
        'Excel' => 'Excel (xls)',
        'Spss' => 'SPSS',
    );

    /**
     * The default values for the form. Defaults for a specific export-type should come
     * from that class
     *
     * @var array
     */
    protected $_defaults = array(
        'type' => 'excel'
    );

    /**
     *
     * @param type $container A container acting as source fro \MUtil_Registry_Source
     * @param array $dirs The directories where to look for requested classes
     */
    public function __construct($container, array $dirs)
    {
        parent::__construct($container, $dirs);

        // Make sure the export is known
        $this->addRegistryContainer(array('export' => $this));
    }

    /**
     * Add one or more export classes
     *
     * @param array $stack classname / description array of sourceclasses
     */
    public function addExportClasses($stack)
    {
        $this->_exportClasses = array_merge($this->_exportClasses, $stack);
    }

    public function getDefaults()
    {
        return $this->_defaults;
    }

    /**
     *
     * @return \Gems_Export_ExportInterface
     */
    public function getExport($type)
    {
        return $this->_getClass($type);
    }

    /**
     * Returns all registered export classes
     *
     * @return array Of classname => description
     */
    public function getExportClasses()
    {
        return $this->_exportClasses;
    }

    /**
     * Set the default options for the form
     *
     * @param array $defaults
     */
    public function setDefaults($defaults)
    {
        $this->_defaults = $defaults;
    }
}