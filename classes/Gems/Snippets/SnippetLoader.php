<?php

/**
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
class Gems_Snippets_SnippetLoader extends \Gems_Loader_TargetLoaderAbstract
    implements \MUtil_Snippets_SnippetLoaderInterface
{
    /**
     * Static variable for debuggging purposes. Toggles the echoing of what snippets
     * are requested and returned.
     *
     * Sometimes it is hard to find out what snippets will be loaded. Use the verbose
     * option to see what snippets are requested and what the resulting snippet
     * is including the full prefix (if any).
     *
     * Use:
     *     \Gems_Snippets_SnippetLoader::$verbose = true;
     * to enable.
     *
     * @var boolean $verbose If true echo information about snippet loading.
     */
    public static $verbose = false;

    /**
     * Allows sub classes of \Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Snippets';

    /**
     * Sets the source of variables and the first directory for snippets
     *
     * @param mixed $source Something that is or can be made into \MUtil_Registry_SourceInterface, otherwise
     * \Zend_Registry is used.
     * @param array $dirs prefix => pathname The inital paths to load from
     */
    public function __construct($source = null, array $dirs = array())
    {
        parent::__construct($source, $dirs);

        $this->addPrefixPath('MUtil_Snippets_Standard', MUTIL_LIBRARY_DIR . '/MUtil/Snippets/Standard', false);
    }

    /**
     * Add prefixed paths to the registry of paths
     *
     * @param string $prefix
     * @param string $path
     * @param boolean $prepend
     * @return \MUtil_Snippets_SnippetLoaderInterface
     */
    public function addPrefixPath($prefix, $path, $prepend = true)
    {
        if ($prepend) {
            $this->_dirs = array($prefix => $path) + $this->_dirs;
        } else {
            $this->_dirs[$prefix] = $path;
        }

        $this->_loader->addPrefixPath($prefix, $path, $prepend);

        return $this;
    }


    /**
     * Searches and loads a .php snippet file.
     *
     * @param string $filename The name of the snippet
     * @param array $extraSourceParameters name/value pairs to add to the source for this snippet
     * @return \MUtil_Snippets_SnippetInterface The snippet
     */
    public function getSnippet($filename, array $extraSourceParameters = null)
    {
        try {
            $this->addRegistryContainer($extraSourceParameters, 'tmpContainer');
            $snippet = $this->_loadClass($filename, true);
            $this->removeRegistryContainer('tmpContainer');
            if (self::$verbose) {
                \MUtil_Echo::r('Loading snippet ' . $filename . '<br/>' . 'Using snippet: ' . get_class($snippet));
               }
        } catch (\Exception $exc) {
            if (self::$verbose) {
                \MUtil_Echo::r($exc->getMessage(), __CLASS__ . '->' .  __FUNCTION__ . '(' . $filename . ')');
            }
            throw $exc;
        }

        return $snippet;
    }

    /**
     * Returns a source of values for snippets.
     *
     * @return \MUtil_Registry_SourceInterface
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
     * @return \MUtil_Snippets_SnippetLoaderInterface
     */
    public function removePrefixPath($prefix, $path = null)
    {
        $this->_loader->removePrefixPath($prefix, $path);

        return $this;
    }

    /**
     * Sets the source of variables for snippets
     *
     * @param \MUtil_Registry_SourceInterface $source
     * @return \MUtil_Snippets_SnippetLoader (continuation pattern)
     */
    public function setSource(\MUtil_Registry_SourceInterface $source)
    {
        throw new \Gems_Exception_Coding('Cannot set source for ' . __CLASS__);
    }
}