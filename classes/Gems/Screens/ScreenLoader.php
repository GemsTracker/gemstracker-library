<?php

/**
 *
 * @package    Gems
 * @subpackage Screens
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens;

/**
 *
 * @package    Gems
 * @subpackage Screens
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Jan 17, 2017 5:07:06 PM
 */
class ScreenLoader extends \Gems_Loader_TargetLoaderAbstract
{
    const RESPONDENT_BROWSE_SCREEN = 'Respondent\\Browse';
    // const RESPONDENT_EDIT_SCREEN = 'Respondent/Browse';
    // const RESPONDENT_SHOW_SCREEN = 'Respondent/Browse';

    /**
     * Each screen type must implement an screen class or interface derived
     * from ScreenInterface specified in this array.
     *
     * @see \Gems\Screens\ScreenInterface
     *
     * @var array containing screenType => screenClass for all screen classes
     */
    protected $_screenClasses = [
        self::RESPONDENT_BROWSE_SCREEN => 'Gems\\Screens\\BrowseScreenInterface',
        ];

    /**
     * Lookup screen class for a screen type. This class or interface should at the very least
     * implement the ScreenInterface.
     *
     * @see \Gems\Screens\ScreenInterface
     *
     * @param string $screenType The type (i.e. lookup directory) to find the associated class for
     * @return string Class/interface name associated with the type
     */
    protected function _getScreenClass($screenType)
    {
        if (isset($this->_screenClasses[$screenType])) {
            return $this->_screenClasses[$screenType];
        } else {
            throw new \Gems_Exception_Coding("No screen class exists for screen type '$screenType'.");
        }
    }

    /**
     *
     * @param string $screenType An screen subdirectory (may contain multiple levels split by '/'
     * @return array An array of type prefix => classname
     */
    protected function _getScreenDirs($screenType)
    {
        $paths = [];
        if (DIRECTORY_SEPARATOR == '/') {
            $screenDir = str_replace('\\', DIRECTORY_SEPARATOR, $screenType);
        } else {
            $screenDir = $screenType;
        }
        foreach ($this->_dirs as $name => $dir) {
            $prefix = $name . '\\Screens\\'. $screenType . '\\';
            $fullPath = $dir . DIRECTORY_SEPARATOR . 'Screens' . DIRECTORY_SEPARATOR . $screenDir;
            if (file_exists($fullPath)) {
                $paths[$prefix] = $fullPath;
            }
        }

        return $paths;
    }

    /**
     * Returns a list of selectable screens with an empty element as the first option.
     *
     * @param string $screenType The type (i.e. lookup directory with an associated class) of the screens to list
     * @return \Gems_tracker_TrackerEventInterface or more specific a $screenClass type object
     */
    protected function _listScreens($screenType)
    {
        $results     = array();
        $screenClass = $this->_getScreenClass($screenType);
        $paths       = $this->_getScreenDirs($screenType);

        foreach ($paths as $prefix => $path) {

            if (file_exists($path)) {
                $eDir = dir($path);
                $parts = explode('\\', $prefix, 2);
                if ($parts) {
                    $name = ' (' . reset($parts) . ')';
                } else {
                    $name = '';
                }

                while (false !== ($filename = $eDir->read())) {

                    if ('.php' === substr($filename, -4)) {
                        $screenName = $prefix . substr($filename, 0, -4);

                        // Take care of double definitions
                        if (! isset($results[$screenName])) {
                            if (! class_exists($screenName, false)) {
                                include($path . DIRECTORY_SEPARATOR . $filename);
                            }

                            $screen = new $screenName();

                            if ($screen instanceof $screenClass) {
                                if ($screen instanceof \MUtil_Registry_TargetInterface) {
                                    $this->applySource($screen);
                                }

                                $results[$screenName] = trim($screen->getScreenLabel()) . $name;
                            }
                            // \MUtil_Echo::track($screenName);
                        }
                    }
                }
            }
        }
        natcasesort($results);
        // \MUtil_Echo::track($paths, $results);

        return $results;
    }

    /**
     * Loads and initiates an screen class and returns the class (without triggering the screen itself).
     *
     * @param string $screenName The class name of the individual screen to load
     * @param string $screenType The type (i.e. lookup directory with an associated class) of the screen
     * @return \Gems_tracker_TrackerEventInterface or more specific a $screenClass type object
     */
    protected function _loadScreen($screenName, $screenType)
    {
        $screenClass = $this->_getScreenClass($screenType);

        // \MUtil_Echo::track($screenName);
        if (! class_exists($screenName, true)) {
            // Autoload is used for Zend standard defined classnames,
            // so if the class is not autoloaded, define the path here.
            $filename = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'Screens' . DIRECTORY_SEPARATOR .
                    strtolower($screenType) . DIRECTORY_SEPARATOR . $screenName . '.php';

            if (! file_exists($filename)) {
                throw new \Gems_Exception_Coding("The screen '$screenName' of type '$screenType' does not exist at location: $filename.");
            }
            // \MUtil_Echo::track($filename);

            include($filename);
        }

        $screen = new $screenName();

        if (! $screen instanceof $screenClass) {
            throw new \Gems_Exception_Coding("The screen '$screenName' of type '$screenType' is not an instance of '$screenClass'.");
        }

        if ($screen instanceof \MUtil_Registry_TargetInterface) {
            $this->applySource($screen);
        }

        return $screen;
    }

    /**
     *
     * @return array screenname => string
     */
    public function listRespondentBrowseScreens()
    {
        return $this->_listScreens(self::RESPONDENT_BROWSE_SCREEN);
    }

    /**
     *
     * @param string $eventName
     * @return \Gems\Screens\Respondent\Browse
     */
    public function loadRespondentBrowseScreen($screenName)
    {
        return $this->_loadScreen($screenName, self::RESPONDENT_BROWSE_SCREEN);
    }

}
