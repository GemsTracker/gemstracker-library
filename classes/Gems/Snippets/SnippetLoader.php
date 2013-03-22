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
 * Gems specific version of the snippet loader
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Gems specific version of the snippet loader
 *
 * Loads snippets like all other classes in gems first with project prefix, then gems, mutil
 * and when all that fails it will try without prefix from the project\snippets and gems\snippets
 * folders
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.5
 */
class Gems_Snippets_SnippetLoader extends Gems_Loader_TargetLoaderAbstract implements MUtil_Snippets_SnippetLoaderInterface
{
    /**
     * Allows sub classes of Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Snippets';

    /**
     * Sets the source of variables and the first directory for snippets
     *
     * @param mixed $source Something that is or can be made into MUtil_Registry_SourceInterface, otheriwse Zend_Registry is used.
     * @param array $dirs prefix => pathname The inital paths to load from
     */
    public function __construct($source = null, array $dirs = array())
    {
        parent::__construct($source, $dirs);

        $noPrefixDirs = array(
            GEMS_LIBRARY_DIR . '/classes/MUtil/Snippets/Standard',
            GEMS_LIBRARY_DIR . '/snippets',
            GEMS_ROOT_DIR . '/application/snippets',
        );
        $this->_loader->addPrefixPath('', $noPrefixDirs, false);
        // $this->_loader->addFallBackPath(); // Enable to allow full class name specification 
    }

    /**
     * Add prefixed paths to the registry of paths
     *
     * @param string $prefix
     * @param string $path
     * @return MUtil_Snippets_SnippetLoaderInterface
     */
    public function addPrefixPath($prefix, $path, $prepend = true)
    {
        $this->_loader->addPrefixPath($prefix, $path, $prepend);

        return $this;
    }


    /**
     * Searches and loads a .php snippet file.
     *
     * @param string $filename The name of the snippet
     * @param array $extraSourceParameters name/value pairs to add to the source for this snippet
     * @return MUtil_Snippets_SnippetInterface The snippet
     */
    public function getSnippet($filename, array $extraSourceParameters = null)
    {
        try {
            $this->addRegistryContainer($extraSourceParameters, 'tmpContainer');
            $snippet = $this->_loadClass($filename, true);
            $this->removeRegistryContainer('tmpContainer');
        } catch (Exception $exc) {
            MUtil_Echo::track($exc->getMessage());
            throw $exc;
        }

        return $snippet;
    }

    /**
     * Returns a source of values for snippets.
     *
     * @return MUtil_Registry_SourceInterface
     */
    public function getSource()
    {
        return $this;
    }

    /**
     * Remove a prefix (or prefixed-path) from the registry
     *
     * @param string $prefix
     * @param string $path OPTIONAL
     * @return MUtil_Snippets_SnippetLoaderInterface
     */
    public function removePrefixPath($prefix, $path = null)
    {
        $this->_loader->removePrefixPath($prefix, $path);

        return $this;
    }

    /**
     * Sets the source of variables for snippets
     *
     * @param MUtil_Registry_SourceInterface $source
     * @return MUtil_Snippets_SnippetLoader (continuation pattern)
     */
    public function setSource(MUtil_Registry_SourceInterface $source)
    {
        throw new Gems_Exception_Coding('Cannot set source for ' . __CLASS__);
    }
}