<?php


/**
 * @package    MUtil
 * @subpackage Form_Element
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Form\Element;

/**
 * @package    MUtil
 * @subpackage Form_Element
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Tabs extends \MUtil\Form\Element\Table
{

    private $_decoratorOptions;


    public function __construct(\Zend_Form $subForm, $spec, $options = null, $tabcolumn = null, $active = null)
    {
        if (isset($options['tabcolumn'])) {
            $this->_decoratorOptions['tabcolumn'] = $options['tabcolumn'];
        }
        if (isset($options['active'])) {
            $this->_decoratorOptions['active'] = $options['active'];
        }
        if (isset($options['selectedTabElement'])) {
            $this->_decoratorOptions['selectedTabElement'] = $options['selectedTabElement'];
        }
        parent::__construct($subForm, $spec, $options);
    }

    /**
     * Load default decorators
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
            $this->addDecorator('Tabs', $this->_decoratorOptions)
                ->addDecorator('Label');
        }
    }
}