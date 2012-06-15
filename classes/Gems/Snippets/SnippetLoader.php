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
 * @subpackage 
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 215 2011-07-12 08:52:54Z michiel $
 */

/**
 * Short description for SnippetLoader
 *
 * Long description for class SnippetLoader (if any)...
 *
 * @package    Gems
 * @subpackage Sample
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 * @deprecated Class deprecated since version 2.0
 */
class Gems_Snippets_SnippetLoader extends Gems_Loader_TargetLoaderAbstract implements MUtil_Snippets_SnippetLoaderInterface
{
    protected $cascade = 'Snippets';

    protected $loader;

    /**
     * @var MUtil_Snippets_SnippetLoader
     */
    protected $backup;

    public function __construct($container = null, $dirs = array()) {
        parent::__construct($container, $dirs);
        $this->backup = new MUtil_Snippets_SnippetLoader($this);
    }


    public function addDirectory($dir)
    {
        if (!array_key_exists('', $this->_dirs)) {
            $this->_dirs[''] = array();
        }
        array_unshift($this->_dirs[''], $dir);

        $this->backup->addDirectory($dir);
    }

    public function addSource($container_or_pairs)
    {
        $this->backup->addSource($container_or_pairs);
    }

    public function getDirectories()
    {
        $this->backup->getDirectories();
    }

    public function getSnippet($filename, array $extraSourceParameters = null)
    {
        try {
            $this->addRegistryContainer($extraSourceParameters, 'tmpContainer');
            $snippet = $this->_loadClass($filename, true);
            $this->removeRegistryContainer('tmpContainer');
        } catch (Exception $exc) {
            MUtil_Echo::track($exc->getMessage());
            throwException($exc);
            //Class loading failed, now defer
            //$snippet = $this->backup->getSnippet($filename, $extraSourceParameters);
        }

        return $snippet;
    }

    public function getSource()
    {
        $this->backup->getSource();
    }

    public function setDirectories(array $dirs)
    {
        $this->backup->setDirectories($dirs);
    }

    public function setSource(MUtil_Registry_SourceInterface $source)
    {
        $this->backup->setSource($source);
    }
}