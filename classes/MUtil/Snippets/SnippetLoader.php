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
 * @package    MUtil
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: SnippetLoader.php 345 2011-07-28 08:39:24Z 175780 $
 */

/**
 * This class handles the loading and processing of snippets.
 *
 * @package    MUtil
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class MUtil_Snippets_SnippetLoader
{
    /**
     * The file locations where to look for snippets.
     *
     * Can be overruled in descendants
     *
     * @var array
     */
    protected $snippetsDirectories;

    /**
     * The information source for snippets.
     *
     * @var MUtil_Registry_SourceInterface
     */
    protected $snippetsSource;

    /**
     * Sets the source of variables and the first directory for snippets
     *
     * @param mixed $source Something that is or can be made into MUtil_Registry_SourceInterface, otheriwse Zend_Registry is used.
     */
    public function __construct($source = null)
    {
        if (! $source instanceof MUtil_Registry_Source) {
            $source = new MUtil_Registry_Source($source);
        }
        $this->setSource($source);
        $this->setDirectories(array(dirname(__FILE__) . '/Standard'));
    }

    /**
     * Add a directory to the front of the list of places where snippets are loaded from.
     *
     * @param string $dir
     * @return MUtil_Snippets_SnippetLoader
     */
    public function addDirectory($dir, $loadDefaults = true)
    {
        if (! in_array($dir, $this->snippetsDirectories)) {
            if (file_exists($dir)) {
                array_unshift($this->snippetsDirectories, $dir);
            }
        }

        return $this;
    }

    /**
     * Add parameter values to the source for snippets.
     *
     * @param mixed $container_or_pairs This function can be called with either a single container or a list of name/value pairs.
     * @return MUtil_Snippets_SnippetLoader
     */
    public function addSource($container_or_pairs)
    {
        if (1 == func_num_args()) {
            $container = $container_or_pairs;
        } else {
            $container = MUtil_Ra::pairs(func_get_args());
        }

        $source = $this->getSnippetsSource();
        $source->addRegistryContainer($container);

        return $this;
    }

    /**
     * Returns the directories where snippets are loaded from.
     *
     * @param array $dirs
     * @return array
     */
    public function getDirectories()
    {
        return $this->snippetsDirectories;
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
        $source = $this->getSource();

        // Add extra parameters when specified
        if ($extraSourceParameters) {
            $extraSourceName = __CLASS__ . '->' . __FUNCTION__;
            $source->addRegistryContainer($extraSourceParameters, $extraSourceName);
        }

        $dirs  = $this->getDirectories();

        $classname = $filename;
        if (strpos($filename, '_') === false) {
            $filename  = $filename . '.php';
        } else {
            $filename  = str_replace('_', '/', $filename) . '.php';
        }

        foreach ($dirs as $dir) {
            $filepath = $dir . '/' . $filename;

            // MUtil_Echo::r($filepath);
            if (file_exists($filepath)) {
                require_once($filepath);

                $snippet = new $classname();

                if ($snippet instanceof MUtil_Snippets_SnippetInterface) {
                    if ($source->applySource($snippet)) {
                        if ($extraSourceParameters) {
                            // Can remove now, it was applied
                            $source->removeRegistryContainer($extraSourceName);
                        }

                        return $snippet;

                    } else {
                        throw new Zend_Exception("Not all parameters set for html snippet: '$filepath'. \n\nRequested variables were: " . implode(", ", $snippet->getRegistryRequests()));
                    }
                } else {
                    throw new Zend_Exception("The snippet: '$filepath' does not implement the MUtil_Snippets_SnippetInterface interface.");
                }
            }
        }

        throw new Zend_Exception("Call for non existing html snippet: '$filename'. \n\nLooking in directories: " . implode(", ", $dirs));
    }

    /**
     * Returns a source of values for snippets.
     *
     * @return MUtil_Registry_SourceInterface
     */
    public function getSource()
    {
        return $this->snippetsSource;
    }

    /**
     * Set the directories where snippets are loaded from.
     *
     * @param array $dirs
     * @return MUtil_Snippets_SnippetLoader (continuation pattern)
     */
    public function setDirectories(array $dirs)
    {
        $this->snippetsDirectories = array_reverse($dirs);

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
        $this->snippetsSource = $source;

        return $this;
    }
}
