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
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Class for general utility functions and access to general utility classes.
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Util extends Gems_Loader_TargetLoaderAbstract
{
    /**
     *
     * @var Gems_Util_BasePath
     */
    protected $basepath;

    /**
     * Allows sub classes of Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Util';

    /**
     *
     * @var Gems_Util_DbLookup
     */
    protected $dbLookup;

    /**
     *
     * @var Gems_Util_Localized
     */
    protected $localized;

    /**
     *
     * @var ArrayObject
     */
    protected $project;

    /**
     *
     * @var Gems_Util_RequestCache
     */
    protected $requestCache;

    /**
     *
     * @var Gems_Util_TrackData
     */
    protected $trackData;

    /**
     *
     * @var Gems_Util_Translated
     */
    protected $translated;


    public function getConsentRejected()
    {
        if (isset($this->project->concentRejected)) {
            return $this->project->concentRejected;
        }

        return 'do not use';
    }

    public function getConsentTypes()
    {
        if (isset($this->project->consentTypes)) {
            $consentTypes = explode('|', $this->project->consentTypes);
        } else {
            $consentTypes = array('do not use', 'consent given');
        }

        return array_combine($consentTypes, $consentTypes);
    }

    public function getCurrentURI($subpath = '')
    {
        static $uri;

        if (! $uri) {
            if(isset($_SERVER['HTTPS'])) {
                $secure = $_SERVER["HTTPS"];

                if (strtolower($secure) == 'off') {
                    $secure = false;
                }
            } else {
                $secure = $_SERVER['SERVER_PORT'] == '443';
            }

            $uri = $secure ? 'https' : 'http';

            $uri .= '://';
            $uri .= $_SERVER['SERVER_NAME'];
            $uri .= $this->basepath->getBasePath();
        }
        if ($subpath && ($subpath[0] != '/')) {
            $subpath = '/' . $subpath;
        }

        return $uri . $subpath;
    }

    /**
     *
     * @return Gems_Util_DbLookup
     */
    public function getDbLookup()
    {
        return $this->_getClass('dbLookup');
    }

    public function getImageUri($imageFile)
    {
        return $this->basepath->getBasePath() . '/' . $this->project->imagedir . '/' . $imageFile;
    }

    /**
     *
     * @return Gems_Util_Localized
     */
    public function getLocalized()
    {
        return $this->_getClass('localized');
    }

    /**
     *
     * @param string  $sourceAction    The action to get the cache from if not the current one.
     * @param boolean $readonly        Optional, tell the cache not to store any new values
     * @return Gems_Util_RequestCache
     */
    public function getRequestCache($sourceAction = null, $readonly = false)
    {
        return $this->_getClass('requestCache', null, array($sourceAction, $readonly));
    }

    /**
     *
     * @return Gems_Util_TrackData
     */
    public function getTrackData()
    {
        return $this->_getClass('trackData');
    }

    /**
     *
     * @return Gems_Util_Translated
     */
    public function getTranslated()
    {
        return $this->_getClass('translated');
    }
}
