<?php

/**
 * @version    $Id$
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Form_SubForm extends \Gems_Form
{
    /**
     * Whether or not form elements are members of an array
     * @var bool
     */
    protected $_isArray = true;

    /**
     * The id of the element that keeps track of the focus
     *
     * Set to false to disable
     *
     * @var string
     */
    public $focusTrackerElementId = null;

    /**
     * Load the default decorators
     *
     * @return void
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorator('FormElements');
            if (!\MUtil_Bootstrap::enabled()) {
                $this->addDecorator('HtmlTag', array('tag' => 'dl'))
                    ->addDecorator('Fieldset')
                    ->addDecorator('DtDdWrapper');
            }
                 
        }
    }
}
