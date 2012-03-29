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
 * @package    MUtil
 * @subpackage Mail
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: Mail.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * Extends standard Zend_Mail with functions for using HTML templates for all mails
 * and adding content using BB Code text.
 *
 * @package    MUtil
 * @subpackage Mail
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class MUtil_Mail extends Zend_Mail
{
    /**
     * HTML Template for html part of the message
     *
     * @var string
     */
    protected $_htmlTemplate;

    /**
     * Returns the the current template
     *
     * @return string
     */
    public function getHtmlTemplate()
    {
        if (! $this->_htmlTemplate) {
            $this->_htmlTemplate = "<html><body>\n{content}\n</body></html>";
        }

        return $this->_htmlTemplate;
    }

    /**
     * Set both the Html and Text versions of a message
     *
     * @param string $content
     * @return MUtil_Mail (continuation pattern)
     */
    public function setBodyBBCode($content)
    {
        $this->setBodyHtml(MUtil_Markup::render($content, 'Bbcode', 'Html'));
        $this->setBodyText(MUtil_Markup::render($content, 'Bbcode', 'Text'));

        return $this;
    }

    /**
     * Sets the HTML body for the message, using a template for html if it exists/
     *
     * @param  string    $html
     * @param  string    $charset
     * @param  string    $encoding
     * @return MUtil_Mail (continuation pattern)
     */
    public function setBodyHtml($html, $charset = null, $encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE)
    {
        if ($template = $this->getHtmlTemplate()) {
            $html = str_replace('{content}', $html, $template);
        }

        return parent::setBodyHtml($html, $charset, $encoding);
    }

    /**
     * Set's a html template in which the message content is placed.
     *
     * @param string $template
     * @return MUtil_Mail MUtil_Mail (continuation pattern)
     */
    public function setHtmlTemplate($template)
    {
        $this->_htmlTemplate = $template;
        return $this;
    }

    /**
     * Set the basic html template with the content of a filename
     *
     * @param string $filename
     * @return MUtil_Mail (continuation pattern)
     */
    public function setHtmlTemplateFile($filename)
    {
        if (file_exists($filename)) {
            $this->setHtmlTemplate(file_get_contents($filename));
        }
        return $this;
    }
}

