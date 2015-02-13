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
 * @package    MUtil
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * A model combines knowedge about a set of data with knowledge required to manipulate
 * that set of data. I.e. it can store data about fields such as type, label, length,
 * etc... and meta data about the object like the current query filter and sort order,
 * with manipulation methods like save(), load(), loadNew() and delete().
 *
 * @see MUtil_Model_ModelAbstract
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Model
{
    /**
     * Url parameter to reset searches
     */
    const AUTOSEARCH_RESET = 'reset';

    /**
     * Indentifier for bridges meta key
     */
    const META_BRIDGES = 'bridges';

    /**
     * In order to keep the url's short and to hide any field names from
     * the user, model identifies key values by using 'id' for a single
     * key value and id1, id2, etc... for multiple keys.
     */
    const REQUEST_ID = 'id';

    /**
     * Helper constant for first key value in multi value key.
     */
    const REQUEST_ID1 = 'id1';

    /**
     * Helper constant for second key value in multi value key.
     */
    const REQUEST_ID2 = 'id2';

    /**
     * Helper constant for third key value in multi value key.
     */
    const REQUEST_ID3 = 'id3';

    /**
     * Helper constant for forth key value in multi value key.
     */
    const REQUEST_ID4 = 'id4';

    /**
     * Default parameter name for sorting ascending.
     */
    const SORT_ASC_PARAM  = 'asort';

    /**
     * Default parameter name for sorting descending.
     */
    const SORT_DESC_PARAM = 'dsort';

    /**
     * Default parameter name for wildcard text search.
     */
    const TEXT_FILTER = 'search';

    /**
     * Type identifiers for calculated fields
     */
    const TYPE_NOVALUE      = 0;

    /**
     * Type identifiers for string fields, default type
     */
    const TYPE_STRING       = 1;

    /**
     * Type identifiers for numeric fields
     */
    const TYPE_NUMERIC      = 2;

    /**
     * Type identifiers for date fields
     */
    const TYPE_DATE         = 3;

    /**
     * Type identifiers for date time fields
     */
    const TYPE_DATETIME     = 4;

    /**
     * Type identifiers for time fields
     */
    const TYPE_TIME         = 5;

    /**
     * Type identifiers for sub models that can return multiple row per item
     */
    const TYPE_CHILD_MODEL  = 6;

    /**
     * The default bridges for each new model
     *
     * @var array string => bridge class
     */
    private static $_bridges = array(
        'display'   => 'DisplayBridge',
        'form'      => 'FormBridge',
        'itemTable' => 'VerticalTableBridge',
        'table'     => 'TableBridge',
    );

    /**
     *
     * @var array of MUtil_Loader_PluginLoader
     */
    private static $_loaders = array();

    /**
     *
     * @var array of global for directory paths
     */
    private static $_nameSpaces = array('MUtil');

    /**
     *
     * @var MUtil_Registry_SourceInterface
     */
    private static $_source;

    /**
     * Static variable for debuggging purposes. Toggles the echoing of e.g. of sql
     * select statements, using MUtil_Echo.
     *
     * Implemention classes can use this variable to determine whether to display
     * extra debugging information or not. Please be considerate in what you display:
     * be as succint as possible.
     *
     * Use:
     *     MUtil_Model::$verbose = true;
     * to enable.
     *
     * @var boolean $verbose If true echo retrieval statements.
     */
    public static $verbose = false;

    /**
     * Add a namespace to all loader
     *
     * @param string $nameSpace The namespace without any trailing _
     * @return boolean True when the namespace is new
     */
    public static function addNameSpace($nameSpace)
    {
        if (!in_array($nameSpace, self::$_nameSpaces)) {
            self::$_nameSpaces[] = $nameSpace;

            foreach (self::$_loaders as $subClass => $loader) {
                if ($loader instanceof MUtil_Loader_PluginLoader) {
                    $loader->addPrefixPath(
                            $nameSpace . '_Model_' . $subClass,
                            $nameSpace . DIRECTORY_SEPARATOR . 'Model' . DIRECTORY_SEPARATOR . $subClass);
                }
            }

            return true;
        }
    }

    /**
     * Returns the plugin loader for bridges
     *
     * @return MUtil_Loader_PluginLoader
     */
    public static function getBridgeLoader()
    {
        return self::getLoader('Bridge');
    }

    /**
     * Returns an arrat of bridge type => class name for
     * getting the default bridge classes for a model.
     *
     * @return array
     */
    public static function getDefaultBridges()
    {
        return self::$_bridges;
    }

    /**
     * Returns the plugin loader for dependencies
     *
     * @return MUtil_Loader_PluginLoader
     */
    public static function getDependencyLoader()
    {
        return self::getLoader('Dependency');
    }

    /**
     * Returns a subClass plugin loader
     *
     * @param string $prefix The prefix to load the loader for. Is CamelCased and should not contain an '_', '/' or '\'.
     * @return MUtil_Loader_PluginLoader
     */
    public static function getLoader($prefix)
    {
        if (! isset(self::$_loaders[$prefix])) {
            $loader = new MUtil_Loader_SourcePluginLoader();

            foreach (self::$_nameSpaces as $nameSpace) {
                $loader->addPrefixPath(
                        $nameSpace . '_Model_' . ucfirst($prefix),
                        $nameSpace . DIRECTORY_SEPARATOR . 'Model' . DIRECTORY_SEPARATOR . ucfirst($prefix));
            }
            $loader->addFallBackPath();

            if (self::$_source instanceof MUtil_Registry_SourceInterface) {
                $loader->setSource(self::$_source);
            }

            self::$_loaders[$prefix] = $loader;
        }

        return self::$_loaders[$prefix];
    }

    /**
     * Get or create the current source
     *
     * @return MUtil_Registry_SourceInterface
     */
    public static function getSource()
    {
        if (! self::$_source instanceof MUtil_Registry_SourceInterface) {
            self::setSource(new MUtil_Registry_Source());
        }

        return self::$_source;
    }

    /**
     * Is a source available
     *
     * @return boolean
     */
    public static function hasSource()
    {
        return self::$_source instanceof MUtil_Registry_SourceInterface;
    }

    /**
     * Sets the plugin loader for bridges
     *
     * @param MUtil_Loader_PluginLoader $loader
     */
    public static function setBridgeLoader(MUtil_Loader_PluginLoader $loader)
    {
        self::setLoader($loader, 'Bridge');
    }

    /**
     * Sets the plugin loader for dependencies
     *
     * @param MUtil_Loader_PluginLoader $loader
     */
    public static function setDependencyLoader(MUtil_Loader_PluginLoader $loader)
    {
        self::setLoader($loader, 'Dependency');
    }

    /**
     * Sets the plugin loader for a subclass
     *
     * @param MUtil_Loader_PluginLoader $loader
     * @param string $prefix The prefix to set  the loader for. Is CamelCased and should not contain an '_', '/' or '\'.
     */
    public static function setLoader(MUtil_Loader_PluginLoader $loader, $prefix)
    {
        self::$_loaders[$prefix] = $loader;
    }

    /**
     * Set the current source for loaders
     *
     * @param MUtil_Registry_SourceInterface $source
     * @param boolean $setExisting When true the source is set for all exiting loader
     * @return void
     */
    public static function setSource(MUtil_Registry_SourceInterface $source, $setExisting = true)
    {
        self::$_source = $source;

        if ($setExisting) {
            foreach (self::$_loaders as $loader) {
                if ($loader instanceof MUtil_Loader_SourcePluginLoader) {
                    $loader->setSource($source);
                }
            }
        }
    }
}
