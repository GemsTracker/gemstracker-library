<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Screens
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens;

use Gems\Cache\HelperAdapter;
use Gems\Exception\Coding;
use Gems\Util\Translated;
use ReflectionClass;
use Zalt\Loader\ProjectOverloader;

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
    const RESPONDENT_BROWSE_SCREEN      = 'Respondent/Browse';
    const RESPONDENT_EDIT_SCREEN        = 'Respondent/Edit';
    const RESPONDENT_SHOW_SCREEN        = 'Respondent/Show';
    const RESPONDENT_SUBSCRIBE_SCREEN   = 'Respondent/Subscribe';
    const RESPONDENT_UNSUBSCRIBE_SCREEN = 'Respondent/Unsubscribe';
    const TOKEN_ASK_SCREEN              = 'Token/Ask';

    protected array $config;

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
        protected readonly Translated $translatedUtil,
        protected readonly HelperAdapter $cache,
        protected readonly ProjectOverloader $overloader,
        array $config,
    )
    {
        if (isset($config['screens'])) {
            $this->config = $config['screens'];
        }
    }

    /**
     * Lookup screen class for a screen type. This class or interface should at the very least
     * implement the ScreenInterface.
     *
     * @see \Gems\Screens\ScreenInterface
     *
     * @param string $screenType The type (i.e. lookup directory) to find the associated class for
     * @return string Class/interface name associated with the type
     * @throws Coding If screenType is not supported.
     */
    protected function _getScreenClass(string $screenType): string
    {
        if (isset($this->_screenClasses[$screenType])) {
            return $this->_screenClasses[$screenType];
        } else {
            throw new Coding("No screen class exists for screen type '$screenType'.");
        }
    }

    /**
     * Returns a list of selectable screens with an empty element as the first option.
     *
     * @param string $screenType The type (i.e. lookup directory with an associated class) of the screens to list
     * @return array of ScreenInterface or more specific a $screenClass type object
     */
    protected function _listScreens(string $screenType): array
    {
        $key = HelperAdapter::createCacheKey([get_called_class(), __FUNCTION__, $screenType]);
        if ($this->cache->hasItem($key)) {
            return $this->translatedUtil->getEmptyDropdownArray() + $this->cache->getCacheItem($key);
        }

        $screenClasses = $this->getScreenClasses($screenType);

        $screenList = [];
        if ($screenClasses) {
            foreach ($screenClasses as $screenName) {
                $screen = $this->_loadSCreen($screenName, $screenType);
                $screenList[$screenName] = $screen->getScreenLabel() . " ({$screenName})";
            }
        }
        asort($screenList);

        $this->cache->setCacheItem($key, $screenList);

        return $this->translatedUtil->getEmptyDropdownArray() + $screenList;
    }

    /**
     * Loads and initiates an screen class and returns the class (without triggering the screen itself).
     *
     * @param string $screenName The class name of the individual screen to load
     * @param string $screenType The type (i.e. lookup directory with an associated class) of the screen
     * @return ScreenInterface or more specific a $screenClass type object
     * @throws Coding If screen name and screen type do not match
     */
    protected function _loadScreen(string $screenName, string $screenType): ScreenInterface
    {
        $screenClass = $this->_getScreenClass($screenType);

        $screen = $this->overloader->create($screenName);

        if (! $screen instanceof $screenClass) {
            throw new Coding("The screen '$screenName' of type '$screenType' is not an instance of '$screenClass'.");
        }

        return $screen;
    }

    /**
     * @param string $screenType
     * @return string[]
     */
    protected function getScreenClasses(string $screenType): array
    {
        if (isset($this->config[$screenType])) {
            return $this->config[$screenType];
        }

        return [];
    }

    /**
     *
     * @return array screenname => string
     */
    public function listRespondentBrowseScreens(): array
    {
        return $this->_listScreens(self::RESPONDENT_BROWSE_SCREEN);
    }

    /**
     *
     * @return array screenname => string
     */
    public function listRespondentEditScreens(): array
    {
        return $this->_listScreens(self::RESPONDENT_EDIT_SCREEN);
    }

    /**
     *
     * @return array screenname => string
     */
    public function listRespondentShowScreens(): array
    {
        return $this->_listScreens(self::RESPONDENT_SHOW_SCREEN);
    }

    /**
     *
     * @return array screenname => string
     */
    public function listSubscribeScreens(): array
    {
        return $this->_listScreens(self::RESPONDENT_SUBSCRIBE_SCREEN);
    }

    /**
     *
     * @return array screenname => string
     */
    public function listTokenAskScreens(): array
    {
        return $this->_listScreens(self::TOKEN_ASK_SCREEN);
    }

    /**
     *
     * @return array screenname => string
     */
    public function listUnsubscribeScreens(): array
    {
        return $this->_listScreens(self::RESPONDENT_UNSUBSCRIBE_SCREEN);
    }

    /**
     *
     * @param string $screenName Name of the screen class
     * @return BrowseScreenInterface
     */
    public function loadRespondentBrowseScreen(string $screenName): BrowseScreenInterface
    {
        $screen = $this->_loadScreen($screenName, self::RESPONDENT_BROWSE_SCREEN);
        /** @var BrowseScreenInterface $screen */
        return $screen;
    }

    /**
     *
     * @param string $screenName Name of the screen class
     * @return EditScreenInterface
     */
    public function loadRespondentEditScreen(string $screenName): EditScreenInterface
    {
        $screen = $this->_loadScreen($screenName, self::RESPONDENT_EDIT_SCREEN);
        /** @var EditScreenInterface $screen */
        return $screen;
    }

    /**
     *
     * @param string $screenName Name of the screen class
     * @return ShowScreenInterface
     */
    public function loadRespondentShowScreen(string $screenName): ShowScreenInterface
    {
        $screen = $this->_loadScreen($screenName, self::RESPONDENT_SHOW_SCREEN);
        /** @var ShowScreenInterface $screen */
        return $screen;
    }

    /**
     *
     * @param string|null $screenName Name of the screen class
     * @return SubscribeScreenInterface|null
     */
    public function loadSubscribeScreen(string|null $screenName): SubscribeScreenInterface|null
    {
        if ($screenName) {
            $screen = $this->_loadScreen($screenName, self::RESPONDENT_SUBSCRIBE_SCREEN);
            /** @var SubscribeScreenInterface $screen */
            return $screen;
        }
        return null;
    }

    /**
     *
     * @param string $screenName Name of the screen class
     * @return AskScreenInterface
     */
    public function loadTokenAskScreen(string $screenName): AskScreenInterface
    {
        $screen = $this->_loadScreen($screenName, self::TOKEN_ASK_SCREEN);
        /** @var AskScreenInterface $screen */
        return $screen;
    }

    /**
     *
     * @param string|null $screenName Name of the screen class
     * @return UnsubscribeScreenInterface|null
     */
    public function loadUnsubscribeScreen(string|null $screenName): UnsubscribeScreenInterface|null
    {
        if ($screenName) {
            $screen = $this->_loadScreen($screenName, self::RESPONDENT_UNSUBSCRIBE_SCREEN);
            /** @var UnsubscribeScreenInterface $screen */
            return $screen;
        }
        return null;
    }
}
