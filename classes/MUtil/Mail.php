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
 */

/**
 * @package MUtil
 */
class MUtil_Mail extends Zend_Mail
{
    private $_htmlTemplate;

    public function getHtmlTemplate()
    {
        if (! $this->_htmlTemplate) {
            $this->_htmlTemplate = "<html><body>\n{content}\n</body></html>";
        }

        return $this->_htmlTemplate;
    }

    public function setBodyBBCode($content)
    {
        $this->setBodyHtml(MUtil_Markup::render($content, 'Bbcode', 'Html'));
        $this->setBodyText(MUtil_Markup::render($content, 'Bbcode', 'Text'));

        return $this;
    }

    /**
     * Sets the HTML body for the message
     *
     * @param  string    $html
     * @param  string    $charset
     * @param  string    $encoding
     * @return Zend_Mail Provides fluent interface
     */
    public function setBodyHtml($html, $charset = null, $encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE)
    {
        if ($template = $this->getHtmlTemplate()) {
            $html = str_replace('{content}', $html, $template);
        }

        return parent::setBodyHtml($html, $charset, $encoding);
    }

    public function setHtmlTemplate($template)
    {
        $this->_htmlTemplate = $template;
        return $this;
    }

    public function setHtmlTemplateFile($filename)
    {
        if (file_exists($filename)) {
            $this->setHtmlTemplate(file_get_contents($filename));
        }
        return $this;
    }
}

