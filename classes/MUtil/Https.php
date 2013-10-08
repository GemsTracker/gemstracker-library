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
 * @subpackage Https
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $id: Html.php 362 2011-12-15 17:21:17Z matijsdejong $
 */

/**
 * Static utility function for determining wether https is on.
 *
 * @package    MUtil
 * @subpackage Https
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Https
{
    /**
     * Reroutes if http was not used
     *
     * @return void
     */
    public static function enforce()
    {
        if (self::on()) {
            return;
        }

        $request    = Zend_Controller_Front::getInstance()->getRequest();
        $url        = 'https://' . $_SERVER['HTTP_HOST'] . $request->getRequestUri();
        $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
        $redirector->gotoUrl($url);

    }

    /**
     * True when the url is a HTTPS url, false when HTTP, null otherwise
     *
     * @return boolean True when HTTPS, false when HTTP, null otherwise
     */
    public static function isHttps($url)
    {
        $url = strtolower(substr($url, 0, 8));

        if ('https://' == $url) {
            return true;
        }

        if ('http://' == substr($url, 0, 7)) {
            return false;
        }
        return null;
    }

    /**
     * True when https is used.
     *
     * @return boolean
     */
    public static function on()
    {
        if (empty($_SERVER['HTTPS'])) {
            return false;
        }

        return ((strtolower($_SERVER['HTTPS']) !== 'off') || ($_SERVER['SERVER_PORT'] == 443));
    }
}