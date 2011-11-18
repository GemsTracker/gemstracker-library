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
 * A special displaygroup, to be displayed in a jQuery tab. Main difference is in the decorators.
 *
 * @version $Id$
 * @author 175780
 * @filesource
 * @package Gems
 * @subpackage Form
 */
class Gems_Form_TabSubForm extends Gems_Form_TableForm
{
    /**
     * For backward compatibility, allow MUtil_Html calls to set or append to the title
     *
     * @param type $method
     * @param type $args
     * @return Gems_Form_TabSubForm
     */
    public function __call($method, $args)
    {
        if ('render' == substr($method, 0, 6)) {
            return parent::__call($method, $args);
        }

        $elem = MUtil_Html::createArray($method, $args);

        $value = $this->getAttrib('title');

        $value .= $elem->render($this->getView());

        $this->setAttrib('title', $value);

        //As the decorator might have been added already, update it also
        $decorator = $this->getDecorator('TabPane');
        $options   = $decorator->getOption('jQueryParams');

        $options['title'] = strip_tags($value);

        $decorator->setOption('jQueryParams', $options);

        return $this;
    }
    
    /**
     * Load default decorators
     *
     * @return void
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorator('FormElements')
                 ->addDecorator(array('table' => 'HtmlTag'), array('tag' => 'table', 'class'=>'formTable'))
                 ->addDecorator(array('tab' => 'HtmlTag'), array('tag' => 'div', 'class' => 'displayGroup'))
                 ->addDecorator('TabPane', array('jQueryParams' => array('containerId' => 'mainForm',
                                                                         'title' => $this->getAttrib('title')),
                                                 'class' => $this->getAttrib('class')));
        }
        return $this;
    }
}
