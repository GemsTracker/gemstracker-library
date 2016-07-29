<?php

/**
 *
 * @package    Gems
 * @subpackage Validate
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Check for one of the two values being filled
 *
 * @package    Gems
 * @subpackage Validate
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class Gems_Validate_OneOf extends \Zend_Validate_Abstract
{
    /**
     * Error codes
     * @const string
     */
    const NEITHER  = 'neither';

    protected $_messageTemplates = array(
        self::NEITHER => "Either '%description%' or '%fieldDescription%' must be entered.",
    );

    /**
     * @var array
     */
    protected $_messageVariables = array(
        'description' => '_description',
        'fieldDescription' => '_fieldDescription'
    );


    protected $_description;

    /**
     * The field name against which to validate
     * @var string
     */
    protected $_fieldName;

    /**
     * Description of field name against which to validate
     * @var string
     */
    protected $_fieldDescription;

    /**
     * Sets validator options
     *
     * @param  string $fieldName  Field name against which to validate
     * $param string $fieldDescription  Description of field name against which to validate
     * @return void
     */
    public function __construct($description, $fieldName, $fieldDescription)
    {
        $this->_description = $description;
        $this->_fieldName = $fieldName;
        $this->_fieldDescription = $fieldDescription;
    }

    /**
     * Defined by \Zend_Validate_Interface
     *
     * Returns true if and only if a token has been set and the provided value
     * matches that token.
     *
     * @param  mixed $value
     * @return boolean
     */
    public function isValid($value, $context = array())
    {
        $this->_setValue((string) $value);

        $fieldSet = (boolean) isset($context[$this->_fieldName]) && $context[$this->_fieldName];
        $valueSet = (boolean) $value;

        if ($valueSet && (! $fieldSet))  {
            return true;
        }

        if ((! $valueSet) && $fieldSet)  {
            return true;
        }

        $this->_error(self::NEITHER);
        return false;
    }
}
