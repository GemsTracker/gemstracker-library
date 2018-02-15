<?php

/**
 * @version    $Id$
 * @package    Gems
 * @subpackage Versions
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * @package    Gems
 * @subpackage Versions
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Versions
{
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
         * be THIS LEVEL plus one.
         *
         * This means that future patches for will be loaded,
         * but that previous patches are ignored.
         */
        return 62;
    }

    /**
     * The official Gems version number
     *
     * @return string
     */
    public final function getGemsVersion()
    {
        return '1.8.4';
    }

    /**
     * The official Gems main version number (with only one dot)
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

        if (APPLICATION_ENV !== 'production' && APPLICATION_ENV !== 'acceptance' && APPLICATION_ENV !== 'demo') {
            $version .= '.' . $this->getBuild() . ' [' . APPLICATION_ENV . ']';
        }

        return $version;
    }
}
