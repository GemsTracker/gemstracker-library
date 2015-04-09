<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @package    MUtil
 * @subpackage ProgressBar
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Added buffer capabilities to JsPush.
 *
 * Also when having trouble on IIS, edit the responseBufferLimit using the IIS Manager:
 * - On the server main page, under "Management", select "Configuration Editor";
 * - under "Section", enter 'system.webServer/handlers';
 * - next to "(Collection)" click "..." OR mark the element "(Collection)" and, under "Actions" und '(Collection)' Element, click "Edit Items";
 * - scroll down until you find your PHP version under "Name";
 * - at the bottom, the Properties are shown an can be edited manually, including responseBufferLimit, which should be set to 0 for flush() to work.
 *
 * The big Pro is that you can edit the properties for everything, not only PHP plus you can work with different versions (or even installations of the same version) of PHP.
 *
 * The source of this wisdom:
 * @link http://stackoverflow.com/questions/7178514/php-flush-stopped-flushing-in-iis7-5
 *
 * @package    MUtil
 * @subpackage ProgressBar
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class MUtil_ProgressBar_Adapter_JsPush extends \Zend_ProgressBar_Adapter_JsPush
{
    /**
     * When true data has been sent.
     *
     * @var boolean
     */
    protected $_dataSent = false;

    /**
     * The number of bytes to pad in Kilobytes
     *
     * This is needed as many servers need extra output passing to avoid buffering.
     *
     * Also this allows you to keep the server buffer high while using this JsPush.
     *
     * @var int
     */
    public $extraPaddingKb = 0;

    /**
     * The number of bytes to pad for the first push communication in Kilobytes. If zero
     * $extraPushPaddingKb is used.
     *
     * This is needed as many servers need extra output passing to avoid buffering.
     *
     * Also this allows you to keep the server buffer high while using this JsPush.
     *
     * @var int
     */
    public $initialPaddingKb = 0;

    /**
     * Outputs given data the user agent.
     *
     * This split-off is required for unit-testing.
     *
     * @param  string $data
     * @return void
     */
    protected function _outputData($data)
    {
        $data = $data . "\n";

        if (! $this->_dataSent) {
            // header("Content-Type: text/html; charset=utf-8");
        }
        if ($this->initialPaddingKb && (! $this->_dataSent)) {
            $padding = $this->initialPaddingKb;
        } else {
            $padding = $this->extraPaddingKb;
        }
        if ($padding) {
            $data = $data . str_repeat(' ', 1024 * $padding);
        }
        $this->_dataSent = true;

        // return parent::_outputData($data);
        return parent::_outputData('<br/>STARTDATA<br/>' . $data . '<br/>ENDDATA<br/>');
    }
}
