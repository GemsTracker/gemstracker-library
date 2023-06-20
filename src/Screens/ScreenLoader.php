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

use Zalt\Loader\ConstructorProjectOverloader;

/**
 *
 * @package    Gems
 * @subpackage Screens
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Jan 17, 2017 5:07:06 PM
 */
class ScreenLoader
{
    const RESPONDENT_BROWSE_SCREEN      = 'Respondent\\Browse';
    const RESPONDENT_EDIT_SCREEN        = 'Respondent\\Edit';
    const RESPONDENT_SHOW_SCREEN        = 'Respondent\\Show';
    const RESPONDENT_SUBSCRIBE_SCREEN   = 'Respondent\\Subscribe';
    const RESPONDENT_UNSUBSCRIBE_SCREEN = 'Respondent\\Unsubscribe';
    const TOKEN_ASK_SCREEN              = 'Token\\Ask';

    protected $config;

    /**
     * Each screen type must implement an screen class or interface derived
     * from ScreenInterface specified in this array.
     *
     * @see \Gems\Screens\ScreenInterface
     *
     * @var array containing screenType => screenClass for all screen classes
     */
    protected $_screenClasses = [
        self::RESPONDENT_BROWSE_SCREEN      => 'Gems\\Screens\\BrowseScreenInterface',
        self::RESPONDENT_EDIT_SCREEN        => 'Gems\\Screens\\EditScreenInterface',
        self::RESPONDENT_SHOW_SCREEN        => 'Gems\\Screens\\ShowScreenInterface',
        self::RESPONDENT_SUBSCRIBE_SCREEN   => 'Gems\\Screens\\SubscribeScreenInterface',
        self::RESPONDENT_UNSUBSCRIBE_SCREEN => 'Gems\\Screens\\UnsubscribeScreenInterface',
        self::TOKEN_ASK_SCREEN              => 'Gems\\Screens\\AskScreenInterface',
        ];

    public function __construct(
        protected ConstructorProjectOverloader $overloader,
    )
    {}

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
            throw new \Gems\Exception\Coding("No screen class exists for screen type '$screenType'.");
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
     * @return ScreenInterface or more specific a $screenClass type object
     */
    protected function _listScreens($screenType)
    {
        $screenClass = $this->_getScreenClass($screenType);
        $paths       = $this->_getScreenDirs($screenType);

        return $this->listClasses($screenClass, $paths, 'getScreenLabel');
    }

    /**
     * Loads and initiates an screen class and returns the class (without triggering the screen itself).
     *
     * @param string $screenName The class name of the individual screen to load
     * @param string $screenType The type (i.e. lookup directory with an associated class) of the screen
     * @return ScreenInterface or more specific a $screenClass type object
     */
    protected function _loadScreen($screenName, $screenType)
    {
        $screenClass = $this->_getScreenClass($screenType);

        $screen = $this->overloader->create($screenName);

        if (! $screen instanceof $screenClass) {
            throw new \Gems\Exception\Coding("The screen '$screenName' of type '$screenType' is not an instance of '$screenClass'.");
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
     * @return array screenname => string
     */
    public function listRespondentEditScreens()
    {
        return $this->_listScreens(self::RESPONDENT_EDIT_SCREEN);
    }

    /**
     *
     * @return array screenname => string
     */
    public function listRespondentShowScreens()
    {
        return $this->_listScreens(self::RESPONDENT_SHOW_SCREEN);
    }

    /**
     *
     * @return array screenname => string
     */
    public function listSubscribeScreens()
    {
        return \Gems\Util\Translated::$emptyDropdownArray + $this->_listScreens(self::RESPONDENT_SUBSCRIBE_SCREEN);
    }

    /**
     *
     * @return array screenname => string
     */
    public function listTokenAskScreens()
    {
        return $this->_listScreens(self::TOKEN_ASK_SCREEN);
    }

    /**
     *
     * @return array screenname => string
     */
    public function listUnsubscribeScreens()
    {
        return \Gems\Util\Translated::$emptyDropdownArray + $this->_listScreens(self::RESPONDENT_UNSUBSCRIBE_SCREEN);
    }

    /**
     *
     * @param string $screenName Name of the screen class
     * @return BrowseScreenInterface
     */
    public function loadRespondentBrowseScreen($screenName)
    {
        return $this->_loadScreen($screenName, self::RESPONDENT_BROWSE_SCREEN);
    }

    /**
     *
     * @param string $screenName Name of the screen class
     * @return EditScreenInterface
     */
    public function loadRespondentEditScreen($screenName)
    {
        return $this->_loadScreen($screenName, self::RESPONDENT_EDIT_SCREEN);
    }

    /**
     *
     * @param string $screenName Name of the screen class
     * @return ShowScreenInterface
     */
    public function loadRespondentShowScreen($screenName)
    {
        return $this->_loadScreen($screenName, self::RESPONDENT_SHOW_SCREEN);
    }

    /**
     *
     * @param string $screenName Name of the screen class
     * @return SubscribeScreenInterface|null
     */
    public function loadSubscribeScreen($screenName)
    {
        if ($screenName) {
            return $this->_loadScreen($screenName, self::RESPONDENT_SUBSCRIBE_SCREEN);
        }
        return null;
    }

    /**
     *
     * @param string $screenName Name of the screen class
     * @return AskScreenInterface
     */
    public function loadTokenAskScreen($screenName)
    {
        return $this->_loadScreen($screenName, self::TOKEN_ASK_SCREEN);
    }

    /**
     *
     * @param string $screenName Name of the screen class
     * @return UnsubscribeScreenInterface|null
     */
    public function loadUnsubscribeScreen($screenName)
    {
        if ($screenName) {
            return $this->_loadScreen($screenName, self::RESPONDENT_UNSUBSCRIBE_SCREEN);
        }
        return null;
    }
}
