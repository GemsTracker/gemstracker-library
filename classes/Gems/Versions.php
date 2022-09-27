<?php

/**
 * @package    Gems
 * @subpackage Versions
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems;

/**
 * @package    Gems
 * @subpackage Versions
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Versions
{
    protected string $environment;

    public function __construct(array $config)
    {
        if (isset($config['app'], $config['app']['env'])) {
            $this->environment = $config['app']['env'];
        }
    }

    /**
     * Build number
     *
     * Primarily used for database patches
     *
     * @return int
     */
    public final function getBuild()
    {
        /**
         * DO NOT FORGET !!! to update GEMS__PATCH_LEVELS:
         *
         * For new installations the initial patch level should
         * be THIS LEVEL
         *
         * This means that future patches for will be loaded,
         * but that previous patches are ignored.
         */
        return 100;
    }

    /**
     * The official \Gems version number
     *
     * @return string
     */
    public final function getGemsVersion()
    {
        return '2.0.0';
    }

    /**
     * The official \Gems main version number (with only one dot)
     *
     * @return string
     */
    public function getMainVersion()
    {
        $gemsVersion = $this->getGemsVersion();
        return substr($gemsVersion, 0, strrpos($gemsVersion, '.'));
    }

    /**
     * An optionally project specific main version number (with only one dot)
     *
     * Can be overruled at project level
     *
     * @return string
     */
    public function getMainProjectVersion()
    {
        return $this->getMainVersion();
    }

    /**
     * An optionally project specific version number
     *
     * Can be overruled at project level
     *
     * @return string
     */
    public function getProjectVersion()
    {
        return $this->getGemsVersion();
    }

    /**
     * The long string versions
     *
     * @return string
     */
    public function getVersion()
    {
        $version = $this->getProjectVersion();

        if ($this->environment !== 'production' && $this->environment !== 'acceptance' && $this->environment !== 'demo') {
            $version .= '.' . $this->getBuild() . ' [' . $this->environment . ']';
        }

        return $version;
    }
}
