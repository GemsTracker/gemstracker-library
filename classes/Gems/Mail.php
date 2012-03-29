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
 * @package    Gems
 * @subpackage Mail
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: Mail.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Mail
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.3
 */
class Gems_Mail extends MUtil_Mail
{
    /**
     * Adds To-header and recipient, $email can be an array, or a single string address
     *
     * @param string|array $email
     * @param string $name
     * @param boolean $bounce When true the e-mail is bounced to the from address
     * @return Zend_Mail Provides fluent interface
     */
    public function addTo($email, $name = '', $bounce = false)
    {
        if ($bounce) {
            $name  = str_replace('@', ' at ', $email);
            $email = $this->getFrom();

            if (! $email) {
                throw new Gems_Exception_Coding('Adding bounce To address while From is not set.');
            }
        }

        return parent::addTo($email, $name);
    }

    /**
     * Returns the the current template
     *
     * @return string
     */
    public function getHtmlTemplate()
    {
        if (! $this->_htmlTemplate) {
            $this->setTemplateStyle();
        }

        return parent::getHtmlTemplate();
    }

    /**
     * Set the template using style as basis
     *
     * @param string $style
     * @return MUtil_Mail (continuation pattern)
     */
    public function setTemplateStyle($style = null)
    {
        if (null == $style) {
            $style = GEMS_PROJECT_NAME;
        }
        $this->setHtmlTemplateFile(APPLICATION_PATH . '/configs/email/' . $style . '.html');

        return $this;
    }
}
