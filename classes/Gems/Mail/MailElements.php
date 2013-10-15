<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $id MailElements.php
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Mail
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Mail_MailElements extends Gems_Registry_TargetAbstract {
	
    /**
     * 
     * @var string Class of the button
     */
	protected $buttonClass = 'button';

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * 
     * @var Zend_Loader
     */
	protected $loader;

    protected $menu;

    /**
     * 
     * @var Zend_Translate;
     */
	protected $translate;

    /**
     * 
     * @var Zend_View
     */
    protected $view;

    /**
     * Create an HTML Body element with CKEditor
     *
     * 
     * @return Gems_Form_Element_CKEditor|Zend_Form_Element_Hidden
     */
    public function createBodyElement($name, $label, $required=false, $hidden=false)
    {
        if ($hidden) {
            return new Zend_Form_Element_Hidden($name);
        }

        $options['required'] = $required;
        $options['label']    = $label;

        return new Gems_Form_Element_CKEditor($name, $options);
    }

	/**
     * Default creator of an E-mail form element (set with SimpleEmails validations)
     *
     * @param $name
     * @param $label
     * @param bool $required
     * @param bool $multi
     * @return Zend_Form_Element_Text
     */
	public function createEmailElement($name, $label, $required = false, $multi = false)
    {
        $options['label']     = $label;
        $options['maxlength'] = 250;
        $options['required']  = $required;
        $options['size']      = 50;

        $element = new Zend_Form_Element_Text($name, $options);

        if ($multi) {
            $element->addValidator('SimpleEmails');
        } else {
            $element->addValidator('SimpleEmail');
        }

        return $element;
    }

   /**
     * Add an element that just displays the value to the user
     *
     * @param string $name Name of element
     * @param mixed $arrayOrKey1 MUtil_Ra::pairs() name => value array
     * @return \MUtil_Form_Element_Exhibitor
     */
    public function createExhibitor($name, $options)
    {

        $element = new MUtil_Form_Element_Exhibitor($name, $options);

        return $element;
    }

    /**
     * Create the container that holds the preview Email HTML text.
     *
     * @param bool $noText Is there no preview text button?
     * @return MUtil_Form_Element_Exhibitor
     */
    public function createPreviewHtmlElement($label=false)
    {
        $options['itemDisplay'] = array(__CLASS__, 'displayMailHtml');
        if ($label) {
        	$options['label'] = $this->translate->_($label);
        } else {
            $options['label'] = $this->translate->_('Preview HTML');
        }
        $options['nohidden']    = true;

        return new MUtil_Form_Element_Exhibitor('preview_html', $options);
    }

    /**
     * Create the container that holds the preview Email Plain text.
     *
     * @return MUtil_Form_Element_Exhibitor
     */
    public function createPreviewTextElement()
    {
        $options['itemDisplay'] = MUtil_Html::create()->pre(false, array('class' => 'mailpreview'));
        $options['label']       = $this->translate->_('Preview text');
        $options['nohidden']    = true;

        return new MUtil_Form_Element_Exhibitor('preview_text', $options);
    }

    public function createSubmitButton($name, $label)
    {
    	$button = new Zend_Form_Element_Submit($name, $label);
    	$button->setAttrib('class', $this->buttonClass);
        return $button;
    }

    public function createTemplateSelectElement($name, $label, $target=false, $list=false, $onChangeSubmit=false)
    {
        $options['label'] = $label;
        
        $select = $this->loader->getModels()->getCommTemplateModel()->getSelect();
        
        if ($target) {
            $select->where('gct_target = ?', $target);
        }
        $options['multiOptions'] = $this->db->fetchPairs($select);
        if (! $list) {
            $options['multiOptions'] = array('' => '') + $options['multiOptions'];
        }
        if ($onChangeSubmit) {
            $options['onchange']     = 'this.form.submit()';
        }
        if ($list) {
            $options['required'] = true;
            $options['size'] = min(count($options['multiOptions']) + 1, 7);
        }

        return new Zend_Form_Element_Select($name, $options);
    }



    public static function displayMailHtml($text)
    {
        $div = MUtil_Html::create()->div(array('class' => 'mailpreview'));
        $div->raw($text);

        return $div;
    }

    public function displayMailFields($mailFields)
    {

    	$mailFieldsRepeater = new MUtil_Lazy_RepeatableByKeyValue($mailFields);
        $mailFieldsHtml     = new MUtil_Html_TableElement($mailFieldsRepeater);
        $mailFieldsHtml->addColumn($mailFieldsRepeater->key, $this->translate->_('Field'));
        $mailFieldsHtml->addColumn($mailFieldsRepeater->value, $this->translate->_('Value'));
        return $mailFieldsHtml;
    }

    public function getEmailOption(array $requestData, $name, $email, $extra = null, $disabledTitle = false, $menuFind = false)
    {
        if (! $email) {
            $email = $this->translate->_('no email adress');
        }

        $text = "\"$name\" <$email>";
        if (null !== $extra) {
            $text .= ": $extra";
        }

        if ($this->view) {
            if ($disabledTitle) {
                $el = MUtil_Html::create()->span($text, array('class' => 'disabled'));

                if ($menuFind && is_array($menuFind)) {
                    $menuFind['allowed'] = true;
                    $menuItem = $this->menu->find($menuFind);
                    if ($menuItem) {
                        $href = $menuItem->toHRefAttribute($requestData);

                        if ($href) {
                            $el = MUtil_Html::create()->a($href, $el);
                            $el->target = $menuItem->get('target', '_BLANK');
                        }
                    }
                }
                $el->title = $disabledTitle;
                $text = $el->render($this->view);
            } else {
                $text = $this->view->escape($text);
            }
        }

        return $text;
    }

    public function getAvailableMailTemplates($list=false, $target=false)
    {
        $select = $this->loader->getModels()->getCommTemplateModel()->getSelect();

        if ($target) {
            $select->where('gct_target = ?', $target);
        }
        $templates = $this->db->fetchPairs($select);
        if (! $list) {
            $templates = array('' => '') + $templates;
        }
        return $templates;
    }


}