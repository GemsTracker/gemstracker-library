<?php

/**
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Make sure a \Gems_Form is used for validation
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
abstract class Gems_Model_ModelTranslatorAbstract extends \MUtil_Model_ModelTranslatorAbstract
{
    /**
     * Create an empty form for filtering and validation
     *
     * @return \MUtil_Form
     */
    protected function _createTargetForm()
    {
        return new \Gems_Form();
    }
}
