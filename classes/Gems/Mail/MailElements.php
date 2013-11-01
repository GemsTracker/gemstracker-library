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

    protected $util;

    /**
     * 
     * @var Zend_View
     */
    protected $view;


    /**
     * Adds a form multiple times in a table
     *
     * You can add your own 'form' either to the model or here in the parameters.
     * Otherwise a form of the same class as the parent form will be created.
     *
     * All elements not yet added to the form are added using a new FormBridge
     * instance using the default label / non-label distinction.
     *
     * @param string $name Name of element
     * @param mixed $arrayOrKey1 MUtil_Ra::pairs() name => value array
     * @return MUtil_Form_Element_Table
     */
    public function addFormTabs($parentBridge, $name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 2);

        /*$options = $this->_mergeOptions($name, $options,
            self::SUBFORM_OPTIONS);*/
        //MUtil_Echo::track($options);
        if (isset($options['form'])) {
            $form = $options['form'];
            unset($options['form']);
        } else {
            $formClass = get_class($parentBridge->getForm());
            $form = new $formClass();
        }
        $parentForm = $parentBridge->getForm();
        $submodel = $parentBridge->getModel()->get($name, 'model');
        if ($submodel instanceof MUtil_Model_ModelAbstract) {
            $bridge = new MUtil_Model_FormBridge($submodel, $form);
            $subItemNumber = 0;
            foreach ($submodel->getItemsOrdered() as $itemName) {
                if (! $form->getElement($name)) {
                    if ($submodel->has($itemName, 'label')) {
                        $bridge->add($itemName);
                        $subelement = $form->getElement($itemName);
                    } else {
                        $bridge->addHidden($itemName);
                    }
                }
            }
        }
        $form->activateJQuery();
        $tabcolumn = 'gctt_lang';
        $element = new Gems_Form_Element_Tabs($form, $name, $options, $tabcolumn);
        
        $parentBridge->getForm()->addElement($element);
        return $element;
    }

    /**
     * Create an HTML Body element with CKEditor
     *
     * 
     * @return Gems_Form_Element_CKEditor|Zend_Form_Element_Hidden
     */
    public function createBodyElement($name, $label, $required=false, $hidden=false, $mailFields=array(), $mailFieldsLabel=false)
    {
        if ($hidden) {
            return new Zend_Form_Element_Hidden($name);
        }

        $options['required'] = $required;
        $options['label']    = $label;

        $mailBody = new Gems_Form_Element_CKEditor($name, $options);
        $mailBody->config['availablefields'] = $mailFields;
        if ($mailFieldsLabel) {
            $mailBody->config['availablefieldsLabel'] = $mailFieldsLabel;
        } else {
            $mailBody->config['availablefieldsLabel'] = $this->translate->_('Fields');
        }

        $mailBody->config['extraPlugins'] .= ',availablefields';
        $mailBody->config['toolbar'][] = array('availablefields');

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
     * Create a multioption select with the different mail process options
     * 
     * @return Zend_Form_Element_Radio
     */
    public function createMethodElement()
    {
        $options = $this->util->getTranslated()->getBulkMailProcessOptions();

        return new Zend_Form_Element_Radio('multi_method', array(
            'label'        => $this->translate->_('Method'),
            'multiOptions' => $options,
            'required'     => true,
            ));
    }

    /**
     * Create the container that holds the preview Email HTML text.
     *
     * @param bool $noText Is there no preview text button?
     * @return MUtil_Form_Element_Exhibitor
     */
    public function createPreviewHtmlElement($label=false)
    {
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

        $query = 'SELECT gems__comm_templates.gct_id_template, gems__comm_templates.gct_name 
        FROM gems__comm_template_translations
        RIGHT JOIN gems__comm_templates ON gems__comm_templates.gct_id_template = gems__comm_template_translations.gctt_id_template
        WHERE gems__comm_template_translations.gctt_subject <> "" 
        AND gems__comm_template_translations.gctt_body <> ""';
        if ($target) {
            $query .= ' AND gems__comm_templates.gct_target = ?';
        }
        $query .= ' GROUP BY gems__comm_templates.gct_id_template';

        if ($target) {
            $options['multiOptions'] = $this->db->fetchPairs($query, $target);
        } else {
            $options['multiOptions'] = $this->db->fetchPairs($query);
        }

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

    /**
     * Style the template previews
     * @param  array $templateArray template data
     * @return array html and text views
     */
    protected function getPreview($templateArray)
    {
        $multi = false;
        if (count($templateArray) > 1) {
            $multi = true;
            $allLanguages = $this->util->getLocalized()->getLanguages();
        }

        $htmlView = MUtil_Html::create()->div();
        $textView = MUtil_Html::create()->div();
        foreach($templateArray as $template) {
            if ($multi) {
                $htmlView->h3()[] = $allLanguages[$template['gctt_lang']];
                $textView->h3()[] = $allLanguages[$template['gctt_lang']];
            }
            
            $content = '';
            if ($template['gctt_subject'] || $template['gctt_body']) {
                $content .= '[b]';
                $content .= $this->_('Subject:');
                $content .= '[/b] [i]';
                $content .= $this->mailer->applyFields($template['gctt_subject']);
                $content .= "[/i]\n\n";
                $content .= $this->mailer->applyFields($template['gctt_body']);       
            }
            $htmlView->div(array('class' => 'mailpreview'))->raw(MUtil_Markup::render($content, 'Bbcode', 'Html'));
            $textView->pre(array('class' => 'mailpreview'))->raw(MUtil_Markup::render($content, 'Bbcode', 'Text'));

        }

        return array('html' => $htmlView, 'text' => $textView);
    }
}