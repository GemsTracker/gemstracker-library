<?php

/**
 *
 * @package    Gems
 * @subpackage Mail
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Mail;

/**
 *
 *
 * @package    Gems
 * @subpackage Mail
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class MailElements extends \Gems\Registry\TargetAbstract {

    /**
     *
     * @var string Class of the button
     */
    protected $buttonClass = 'button';

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems\Form
     */
    protected $_form;

    /**
     *
     * @var \Zend_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems\Menu
     */
    protected $menu;

    /**
     *
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    /**
     *
     * @var \Zend_Translate;
     */
    protected $translate;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     *
     * @var \Zend_View
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
     * @param \MUtil\Model\Bridge\FormBridgeInterface $parentBridge
     * @param string $name Name of element
     * @param mixed $arrayOrKey1 \MUtil\Ra::pairs() name => value array
     * @return \MUtil\Form\Element\Table
     */
    public function addFormTabs($parentBridge, $name, $arrayOrKey1 = null)
    {
        $options = func_get_args();
        $options = \MUtil\Ra::pairs($options, 2);

        /* $options = $this->_mergeOptions($name, $options,
          self::SUBFORM_OPTIONS); */
        //\MUtil\EchoOut\EchoOut::track($options);
        if (isset($options['form'])) {
            $form = $options['form'];
            unset($options['form']);
        } else {
            $formClass = get_class($parentBridge->getForm());
            $form      = new $formClass();
        }
        $submodel = $parentBridge->getModel()->get($name, 'model');
        if ($submodel instanceof \MUtil\Model\ModelAbstract) {
            $bridge = $submodel->getBridgeFor('form', $form);
            foreach ($submodel->getItemsOrdered() as $itemName) {
                if (!$form->getElement($name)) {
                    if ($submodel->has($itemName, 'label')) {
                        $bridge->add($itemName);
                    } else {
                        $bridge->addHidden($itemName);
                    }
                }
            }
        }
        $form->activateJQuery();
        $element = new \Gems\Form\Element\Tabs($form, $name, $options);

        $parentBridge->getForm()->addElement($element);
        return $element;
    }

    /**
     * Create an HTML Body element with CKEditor
     *
     *
     * @return \Gems\Form\Element\CKEditor|\Zend_Form_Element_Hidden
     */
    public function createBodyElement($name, $label, $required = false, $hidden = false, $mailFields = array(), $mailFieldsLabel = false)
    {
        if ($hidden) {
            return new \Zend_Form_Element_Hidden($name);
        }

        $options['required'] = $required;
        $options['label']    = $label;

        $mailBody                            = new \Gems\Form\Element\CKEditor($name, $options);
        $mailBody->config['availablefields'] = $mailFields;
        if ($mailFieldsLabel) {
            $mailBody->config['availablefieldsLabel'] = $mailFieldsLabel;
        } else {
            $mailBody->config['availablefieldsLabel'] = $this->translate->_('Fields');
        }

        $mailBody->config['extraPlugins'] .= ',availablefields';
        $mailBody->config['toolbar'][]    = array('availablefields');

        return new \Gems\Form\Element\CKEditor($name, $options);
    }

    /**
     * Default creator of an E-mail form element (set with SimpleEmails validations)
     *
     * @param $name
     * @param $label
     * @param bool $required
     * @param bool $multi
     * @return \Zend_Form_Element_Text
     */
    public function createEmailElement($name, $label, $required = false, $multi = false)
    {
        $options['label']     = $label;
        $options['maxlength'] = 250;
        $options['required']  = $required;
        $options['size']      = 50;

        $element = $this->_form->createElement('text', $name, $options);

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
     * @return \Zend_Form_Element_Radio
     */
    public function createMethodElement()
    {
        $multiOptions = $this->util->getCommJobsUtil()->getBulkProcessOptions();
        $options      = array(
            'label'        => $this->translate->_('Method'),
            'multiOptions' => $multiOptions,
            'required'     => true,
        );

        return $this->_form->createElement('radio', 'multi_method', $options);
    }

    /**
     * Create the container that holds the preview Email HTML text.
     *
     * @param bool $noText Is there no preview text button?
     * @return \MUtil\Form\Element\Exhibitor
     */
    public function createPreviewHtmlElement($label = false)
    {
        if ($label) {
            $options['label'] = $this->translate->_($label);
        } else {
            $options['label'] = $this->translate->_('Preview HTML');
        }
        $options['nohidden'] = true;

        return $this->_form->createElement('Html', 'preview_html', $options);
    }

    /**
     * Create the container that holds the preview Email Plain text.
     *
     * @return \MUtil\Form\Element\Exhibitor
     */
    public function createPreviewTextElement()
    {
        $options['label']    = $this->translate->_('Preview Text');
        $options['nohidden'] = true;

        return $this->_form->createElement('Html', 'preview_text', $options);
    }

    /**
     *
     * @param string $name
     * @param string $label
     * @return \Zend_Form_Element_Submit
     */
    public function createSubmitButton($name, $label)
    {
        $button = $this->_form->createElement('submit', $name, array('label' => $label));
        return $button;
    }

    public function createTemplateSelectElement($name, $label, $target = false, $list = false, $onChangeSubmit = false)
    {
        $query = 'SELECT gems__comm_templates.gct_id_template, gems__comm_templates.gct_name
        FROM gems__comm_template_translations
        RIGHT JOIN gems__comm_templates ON gems__comm_templates.gct_id_template = gems__comm_template_translations.gctt_id_template
        WHERE gems__comm_template_translations.gctt_subject <> ""
        AND gems__comm_template_translations.gctt_body <> ""';
        if ($target) {
            $query .= $this->db->quoteInto(' AND gems__comm_templates.gct_target = ?', $target);
        }
        $query .= ' GROUP BY gems__comm_templates.gct_id_template ORDER BY gems__comm_templates.gct_name';

        $options['multiOptions'] = $this->db->fetchPairs($query);
        $options['label']        = $label;

        if ($onChangeSubmit) {
            $options['onchange'] = 'this.form.submit()';
        }

        if ($list) {
            $options['required'] = true;
            $options['size']     = min(count($options['multiOptions']) + 1, 7);
        } else {
            $options['multiOptions'] = array('' => '') + $options['multiOptions'];
        }

        return $this->_form->createElement('select', $name, $options);
    }

    public static function displayMailHtml($text)
    {
        $div = \MUtil\Html::create()->div(array('class' => 'mailpreview'));
        $div->raw($text);

        return $div;
    }

    /**
     * Returns an html element that displays the array of mauilfields
     *
     * @param array $mailFields
     * @return \MUtil\Html\HtmlElement
     * @throws \MUtil\Lazy\LazyException
     */
    public function displayMailFields(array $mailFields)
    {
        ksort($mailFields);
        $mailFieldsRepeater = new \MUtil\Lazy\RepeatableByKeyValue($mailFields);
        $mailFieldsHtml     = new \MUtil\Html\TableElement($mailFieldsRepeater, array('class' => 'table table-striped table-bordered table-condensed'));
        $mailFieldsHtml->addColumn($mailFieldsRepeater->key, $this->translate->_('Field'));
        $mailFieldsHtml->addColumn($mailFieldsRepeater->value, $this->translate->_('Value'));

        $container   = \MUtil\Html::create()->div(['class' => 'table-container']);
        $container[] = $mailFieldsHtml;

        return $container;
    }

    public function getEmailOption(array $requestData, $name, $email, $extra = null, $disabledTitle = false, $menuFind = false)
    {
        if (!$email) {
            $email = $this->translate->_('no email adress');
        }

        $text = "\"$name\" <$email>";
        if (null !== $extra) {
            $text .= ": $extra";
        }

        if ($this->view) {
            if ($disabledTitle) {
                $element = \MUtil\Html::create()->span($text, array('class' => 'disabled'));

                if ($menuFind && is_array($menuFind)) {
                    $menuFind['allowed'] = true;
                    $menuItem            = $this->menu->find($menuFind);
                    if ($menuItem) {
                        $href = $menuItem->toHRefAttribute($requestData);

                        if ($href) {
                            $element         = \MUtil\Html::create()->a($href, $element);
                            $element->target = $menuItem->get('target', '_BLANK');
                        }
                    }
                }
                $element->title = $disabledTitle;
                $text           = $element->render($this->view);
            } else {
                $text = $this->view->escape($text);
            }
        }

        return $text;
    }

    public function setForm($form)
    {
        $this->_form = $form;
    }
}
