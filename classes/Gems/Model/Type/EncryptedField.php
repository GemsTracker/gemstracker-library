<?php

/*

/**
 * Allow encryption of some database fields like passwords
 *
 * Only use for passwords the application needs to use like database passwords
 * etc. The user passwords are stored using a one-way encryption.
 *
 * @package    Gems
 * @subpackage Model\Type
 * @author     175780
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */
class Gems_Model_Type_EncryptedField
{
    /**
     * Array encryption value field name => encryption method field name
     *
     * @var array
     */
    protected $findValue;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * Shoudl the value be masked?
     *
     * @var boolean
     */
    protected $valueMask;

    public function __construct(\Gems_Project_ProjectSettings $project, $valueMask = true)
    {
        $this->project   = $project;
        $this->valueMask = $valueMask;
    }

    /**
     * Use this function for a default application of this type to the model
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param string $valueField The field containing the value to be encrypted
     * #param string $methodField the field storing the method of encryption
     * @return \Gems_Model_Type_EncryptedField (continuation pattern)
     */
    public function apply(\MUtil_Model_ModelAbstract $model, $valueField, $methodField)
    {
        $this->findValue[$methodField] = $valueField;

        $model->setSaveWhenNotNull($valueField);
        $model->setOnLoad($valueField, array($this, 'loadValue'));
        $model->setOnSave($valueField, array($this, 'saveValue'));

        // Only hidden to make sure onSave's are triggered
        $model->set($methodField, 'elementClass', 'hidden');
        $model->setOnLoad($methodField, 'default'); // Yes you can set this to a constant
        $model->setSaveWhen($methodField, array($this, 'whenEncryption'));
        $model->setOnSave($methodField, array($this, 'saveEncryption'));

        if ($model instanceof \MUtil_Model_DatabaseModelAbstract) {
            $model->setOnTextFilter($valueField, false);
            $model->setOnTextFilter($methodField, false);
        }

        return $this;
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a
     * dateformat read from the database to a \Zend_Date format
     *
     * If empty or \Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return \MUtil_Date|\Zend_Db_Expr|string
     */
    public function loadValue($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        if ($value && (! $isPost)) {
            if ($this->valueMask) {
                return str_repeat('*', 8);
            } else {
                $methodField = array_search($name, $this->findValue);
                if (array_key_exists($methodField, $context)) {
                    return $this->project->decrypt($value, $context[$methodField]);
                }
            }
        }

        return $value;
    }

    /**
     * A ModelAbstract->setOnSave() function that returns the input
     * date as a valid date.
     *
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return \Zend_Date
     */
    public function saveEncryption($value, $isNew = false, $name = null, array $context = array())
    {
        $valueField = $this->findValue[$name];
        if (isset($context[$valueField]) && $context[$valueField]) {
            return 'default';
        }
        return null;
    }

    /**
     * A ModelAbstract->setOnSave() function that returns the input
     * date as a valid date.
     *
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return \Zend_Date
     */
    public function saveValue($value, $isNew = false, $name = null, array $context = array())
    {
        if ($value) {
            // \MUtil_Echo::track($value);
            return $this->project->encrypt($value, 'default');
        }
    }

    /**
     * A ModelAbstract->setOnSave() function that returns the input
     * date as a valid date.
     *
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return \Zend_Date
     */
    public function whenEncryption($value, $isNew = false, $name = null, array $context = array())
    {
        // \MUtil_Echo::track($value);
        $valueField = $this->findValue[$name];
        return isset($context[$valueField]) && $context[$valueField];
    }
}
