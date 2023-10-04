<?php

/**
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems;

use Zalt\Html\ElementInterface;
use Zalt\Html\UrlArrayAttribute;
use Zalt\Ra\Ra;

/**
 * Base form class with extensions for correct load paths, autosubmit forms and registry use.
 *
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Form extends \MUtil\Form
{
    /**
     * Constructor
     *
     * Registers form view helper as decorator
     *
     * @param string $name
     * @param mixed $options
     * @return void
     */
    public function __construct($options = null)
    {
        parent::__construct($options);

        $this->addPrefixPath('Gems_Form_Decorator', 'Form/Decorator/', \Zend_Form::DECORATOR);
        $this->addPrefixPath('Gems_Form_Element',   'Form/Element/',   \Zend_Form::ELEMENT);

        $this->addElementPrefixPath('Gems\Form_Decorator',  'Form/Decorator/',  \Zend_Form_Element::DECORATOR);
        $this->addElementPrefixPath('Gems_Filter',          'Filter/',          \Zend_Form_Element::FILTER);
        $this->addElementPrefixPath('Gems_Validator',       'Validator/',       \Zend_Form_Element::VALIDATE);

        $this->setDisableTranslator(true);

        $this->activateBootstrap();
    }

    /**
     * Activate JQuery for this form
     *
     * @return \MUtil\Form (continuation pattern)
     */
    public function activateJQuery()
    {
        if ($this->_no_jquery) {
            parent::activateJQuery();

            $this->addPrefixPath('Gems_JQuery_Form_Decorator', 'Gems/JQuery/Form/Decorator/', \Zend_Form::DECORATOR);
            $this->addPrefixPath('Gems_JQuery_Form_Element', 'Gems/JQuery/Form/Element/', \Zend_Form::ELEMENT);
        }

        return $this;
    }

    /**
     * Change the form into an autosubmit form
     *
     * @param mixed $submitUrl Url as \MUtil\Html\UrlArrayAttribute, array or string
     * @param mixed $targetId Id of html element whose content is replaced by the submit result: \MUtil\Html\ElementInterface or string
     */
    public function setAutoSubmit($submitUrl, $targetId)
    {
        // Filter out elements passed by type
        $args = Ra::args(func_get_args(),
            array(
                'submitUrl' => array(UrlArrayAttribute::class, \MUtil\Html\UrlArrayAttribute::class,  'is_array', 'is_string'),
                'targetId'  => array(ElementInterface::class, \MUtil\Html\ElementInterface::class, 'is_string'),
                ), null, Ra::STRICT);

        if (isset($args['targetId'])) {
            if ($args['targetId'] instanceof ElementInterface || $args['targetId'] instanceof \MUtil\Html\ElementInterface) {
                if (isset($args['targetId']->id)) {
                    $args['targetId'] = '#' . $args['targetId']->id;
                } elseif (isset($args['targetId']->class)) {
                    $args['targetId'] = '.' . $args['targetId']->class;
                } else {
                    $args['targetId'] = $args['targetId']->getTagName();
                }
            } else {
                $args['targetId'] = '#' . $args['targetId'];
            }

            $this->setAttrib('autosubmit-target-id', $args['targetId']);
        }

        $this->setAttrib('autosubmit-url', $args['submitUrl']);
        $this->setAttrib('class', $this->getAttrib('class') . ' ' . 'autosubmit');
    }
}