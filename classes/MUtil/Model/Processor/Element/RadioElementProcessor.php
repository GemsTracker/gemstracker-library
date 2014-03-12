<?php

/**
 * Copyright (c) 2014, J-POP Foundation
 * All rights reserved.
 *
 * @package    MUtil
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 J-POP Foundation
 * @license    no free license, do not use without permission
 * @version    $Id: RadioElementProcessor .php 1748 2014-02-19 18:09:41Z matijsdejong $
 */

/**
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2014 J-POP Foundation
 * @license    no free license, do not use without permission
 * @since      Class available since 2014 $(date} 21:13:21
 */
class MUtil_Model_Processor_Element_RadioElementProcessor extends MUtil_Model_Processor_ElementProcessorAbstract
{
    /**
     * Allow use of answers select specific options
     *
     * @var boolean
     */
    protected $useMultiOptions = true;

    /**
     * Processes the input, changing e.g. the result, context or options
     *
     * @param MUtil_Model_Input $input
     * @return void
     */
    public function process(MUtil_Model_Input $input)
    {
        $options = $this->getFilteredOptions($input);

        // Is sometimes added automatically, but should not be used here
        unset($options['maxlength']);

        $this->applyElement(
                $input,
                new Zend_Form_Element_Radio($input->getName(), $options)
                );
    }
}
