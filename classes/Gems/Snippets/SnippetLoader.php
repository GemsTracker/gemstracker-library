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

    protected $loader;

    /**
     * @var MUtil_Snippets_SnippetLoader
     */
    protected $backup;

    /**
     * Initialize the snippetloader (Gems style)
     *
     * @param mixed $container A container acting as source for MUtil_Registry_Source
     * @param array $dirs The directories where to look for requested classes
     */
    public function __construct($container = null, $dirs = array())
    {
        // Add tracker snippets directory
        $dirs['Gems_Tracker'] = realpath(__DIR__ . '/../..');

        parent::__construct($container, $dirs);

        $this->backup = new MUtil_Snippets_SnippetLoader($this);
        $this->addDirectory(GEMS_LIBRARY_DIR . '/classes/MUtil/Snippets/Standard');
    }


    /**
     * Add a directory to the front of the list of places where snippets are loaded from.
     *
     * @param string $dir
     * @return MUtil_Snippets_SnippetLoader
     */
    public function addDirectory($dir)
    {
        if (!array_key_exists('', $this->_dirs)) {
            $this->_dirs[''] = array();
        }
        array_unshift($this->_dirs[''], $dir);

        return $this->backup->addDirectory($dir);
    }

    /**
     * Add parameter values to the source for snippets.
     *
     * @param mixed $container_or_pairs This function can be called with either a single container or a list of name/value pairs.
     * @return MUtil_Snippets_SnippetLoader
     */
    public function addSource($container_or_pairs)
    {
        return $this->backup->addSource($container_or_pairs);
    }

    /**
     * Returns the directories where snippets are loaded from.
     *
     * @param array $dirs
     * @return array
     */
    public function getDirectories()
    {
        return $this->backup->getDirectories();
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
            //Class loading failed, now defer
            //$snippet = $this->backup->getSnippet($filename, $extraSourceParameters);
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
        return $this->backup->getSource();
    }

    /**
     * Set the directories where snippets are loaded from.
     *
     * @param array $dirs
     * @return MUtil_Snippets_SnippetLoader (continuation pattern)
     */
    public function setDirectories(array $dirs)
    {
        return $this->backup->setDirectories($dirs);
    }

    /**
     * Sets the source of variables for snippets
     *
     * @param MUtil_Registry_SourceInterface $source
     * @return MUtil_Snippets_SnippetLoader (continuation pattern)
     */
    public function setSource(MUtil_Registry_SourceInterface $source)
    {
        return $this->backup->setSource($source);
    }
}