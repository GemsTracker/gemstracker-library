<?php

/**
 *
 * @package    Gems
 * @subpackage User\Embed
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\User\Embed;

use MUtil\Translate\TranslateableTrait;

/**
 *
 * @package    Gems
 * @subpackage User\Embed
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 01-Apr-2020 15:55:10
 */
class EmbedLoader extends \Gems_Loader_TargetLoaderAbstract
{
    use TranslateableTrait;

    const AUTHENTICATE          = 'Auth';
    const DEFERRED_USER_LOADER  = 'DeferredUserLoader';
    const REDIRECT              = 'Redirect';

    const SUB_NAMESPACE         = 'User\\Embed';

    /**
     * Each embed helper type must implement an embed helper class or interface derived
     * from EmbedHelperInterface specified in this array.
     *
     * @see \Pulse\User\Embed\HelperInterface
     *
     * @var array containing helperType => helperInterface for all helper classes
     */
    protected $_helperClasses = [
        self::AUTHENTICATE              => 'Gems\\User\\Embed\\EmbeddedAuthInterface',
        self::DEFERRED_USER_LOADER      => 'Gems\\User\\Embed\\DeferredUserLoaderInterface',
        self::REDIRECT                  => 'Gems\\User\\Embed\\RedirectInterface',
    ];

    /**
     *
     * @param string $type An screen subdirectory (may contain multiple levels split by '/'
     * @return array An array of type prefix => classname
     */
    protected function _getDirs($type)
    {
        $paths = [];
        if (DIRECTORY_SEPARATOR == '/') {
            $typeDir = str_replace('\\', DIRECTORY_SEPARATOR, $type);
        } else {
            $typeDir = $type;
        }
        foreach ($this->_dirs as $name => $dir) {
            $prefix = $name . '\\' . self::SUB_NAMESPACE . '\\'. $type . '\\';
            $subDir = str_replace('\\', DIRECTORY_SEPARATOR, self::SUB_NAMESPACE);
            $fullPath = $dir . DIRECTORY_SEPARATOR . $subDir . DIRECTORY_SEPARATOR . $typeDir;
            if (file_exists($fullPath)) {
                $paths[$prefix] = $fullPath;
            }
        }

        return $paths;
    }

    /**
     * Lookup class for an embedded helper type. This class or interface should at the very least
     * implement the HelperInterface.
     *
     * @see \Pulse\User\Embed\HelperInterface
     *
     * @param string $screenType The type (i.e. lookup directory) to find the associated class for
     * @return string Class/interface name associated with the type
     */
    protected function _getInterface($helperType)
    {
        if (isset($this->_helperClasses[$helperType])) {
            return $this->_helperClasses[$helperType];
        } else {
            throw new \Gems_Exception_Coding("No embedded helper class exists for helper type '$helperType'.");
        }
    }

    /**
     *
     * @return type => dir
     */
    protected function _getLayoutDirs()
    {
        // Do NOT use $this->_dirs as that points to the class paths
        return [
            'Gems' => GEMS_LIBRARY_DIR . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'scripts',
            GEMS_PROJECT_NAME_UC => APPLICATION_PATH . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'scripts',
        ];
    }

    /**
     * Loads and initiates an embed class and returns the class
     *
     * @param string $helperName The class name of the individual embed helper to load
     * @param string $helperType The type (i.e. lookup directory with an associated class) of the helper
     * @return object The helper class
     */
    protected function _loadClassOfType($helperName, $helperType)
    {
        $helperClass = $this->_getInterface($helperType);

        $helper = new $helperName();

        if (! $helper instanceof $helperClass) {
            throw new \Gems_Exception_Coding("The class '$helperName' of type '$helperType' is not an instance of '$helperClass'.");
        }

        if ($helper instanceof \MUtil_Registry_TargetInterface) {
            $this->applySource($helper);
        }

        return $helper;
    }

    /**
     * Returns a list of selectable screens with an empty element as the first option.
     *
     * @param string $helperType The type (i.e. lookup directory with an associated class) of the helper to list
     * @return HelperInterface or more specific a helperclass type object
     */
    protected function _listClasses($helperType)
    {
        $classType = $this->_getInterface($helperType);
        $paths       = $this->_getDirs($helperType);

        return $this->listClasses($classType, $paths, 'getLabel');
    }

    /**
     *
     * @return array helpername => string
     */
    public function listAuthenticators()
    {
        return $this->_listClasses(self::AUTHENTICATE);
    }

    /**
     *
     * @return array helpername => string
     */
    public function listDeferredUserLoaders()
    {
        return $this->_listClasses(self::DEFERRED_USER_LOADER);
    }

    /**
     * Get an array of layouts in Gemstracker and Project
     *
     * @return array
     */
    public function listLayouts()
    {
        $paths = $this->_getLayoutDirs();

        $layouts = ['' => $this->_('Do not change layout')];
        $extension = '.phtml';
        foreach($paths as $type => $path) {
            $globIter = new \GlobIterator($path . DIRECTORY_SEPARATOR . '*'.$extension);

            foreach($globIter as $fileInfo) {
                $name = $fileInfo->getFilename();
                $baseName = str_replace($extension, '', $name);
                $layouts[$type][$baseName] = $baseName;
            }
        }

        return $layouts;
    }

    /**
     *
     * @return array helpername => string
     */
    public function listRedirects()
    {
        return $this->_listClasses(self::REDIRECT);
    }

    /**
     * Get an array of Escort styles
     *
     * @return array
     */
    public function listStyles()
    {
        $escort = \GemsEscort::getInstance();

        if ($escort instanceof \Gems_Project_Layout_MultiLayoutInterface) {
            $styles[GEMS_PROJECT_NAME_UC] = $escort->getStyles();
        } else {
            $styles = [];
        }

        return ['' => $this->_('Use organization style')] + $styles;
    }

    /**
     *
     * @param string $helperName Name of the helper class
     * @return \Gems\User\Embed\EmbeddedAuthInterface
     */
    public function loadAuthenticator($helperName)
    {
        return $this->_loadClassOfType($helperName, self::AUTHENTICATE);
    }

    /**
     *
     * @param string $helperName Name of the helper class
     * @return \Gems\User\Embed\DeferredUserLoaderInterface
     */
    public function loadDeferredUserLoader($helperName)
    {
        return $this->_loadClassOfType($helperName, self::DEFERRED_USER_LOADER);
    }

    /**
     *
     * @param string $helperName Name of the helper class
     * @return \Gems\User\Embed\RedirectInterface
     */
    public function loadRedirect($helperName)
    {
        return $this->_loadClassOfType($helperName, self::REDIRECT);
    }
}