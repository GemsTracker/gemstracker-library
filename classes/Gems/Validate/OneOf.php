<?php

/**
 *
 * @package    Gems
 * @subpackage Validate
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Validate;

/**
 * Check for one of the two values being filled
 *
 * @package    Gems
 * @subpackage Validate
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class OneOf extends \Laminas\Validator\AbstractValidator
{
    /**
     * Error codes
     * @const string
     */
    const NEITHER  = 'neither';

    protected array $messageTemplates = [
        self::NEITHER => "Either '%description%' or '%fieldDescription%' must be entered.",
    ];

    /**
     * @var array
     */
    protected array $messageVariables = [
        'description' => '_description',
        'fieldDescription' => '_fieldDescription'
    ];


    protected string $description;

    /**
     * The field name against which to validate
     * @var string
     */
    protected string $fieldName;

    /**
     * Description of field name against which to validate
     * @var string
     */
    protected string $fieldDescription;

    /**
     * Sets validator options
     *
     * @param  string $fieldName  Field name against which to validate
     * $param string $fieldDescription  Description of field name against which to validate
     * @return void
     */
    public function __construct(string $description, string $fieldName, string $fieldDescription)
    {
        parent::__construct();
        $this->description = $description;
        $this->fieldName = $fieldName;
        $this->fieldDescription = $fieldDescription;
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
    public function isValid($value, $context = [])
    {
        $this->setValue((string) $value);

        $fieldSet = isset($context[$this->_fieldName]) && $context[$this->_fieldName];
        $valueSet = (boolean) $value;

        if ($valueSet && (! $fieldSet))  {
            return true;
        }

        if ((! $valueSet) && $fieldSet)  {
            return true;
        }

        $this->error(self::NEITHER);
        return false;
    }
}
