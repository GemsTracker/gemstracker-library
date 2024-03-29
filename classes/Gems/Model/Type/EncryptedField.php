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
 */

/**
 *
 * @package    Gems
 * @subpackage Model\Type
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.7
 */
class Gems_Model_Type_EncryptedField
{
    /**
     * @var string 
     */
    protected $maskedValue = '********';
    
    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * Should the value be masked?
     *
     * @var boolean
     */
    protected $valueMask;

    /**
     *
     * @param \Gems_Project_ProjectSettings $project
     * @param boolean $valueMask
     */
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
     * @return \Gems_Model_Type_EncryptedField (continuation pattern)
     */
    public function apply(\MUtil_Model_ModelAbstract $model, $valueField)
    {
        $model->setSaveWhen($valueField, array($this, 'saveWhen'));
        $model->setOnLoad($valueField, array($this, 'loadValue'));
        $model->setOnSave($valueField, array($this, 'saveValue'));

        if ($model instanceof \MUtil_Model_DatabaseModelAbstract) {
            $model->setOnTextFilter($valueField, false);
        }
        if ($model->get($valueField, 'repeatLabel')) {
            $repeatField = $valueField . '__repeat';
            $model->set($repeatField, 'elementClass', 'None');
            $model->setSaveWhen($repeatField, false);
            $model->setOnLoad($repeatField, array($this, 'loadValue'));
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
        if (\MUtil_String::endsWith($name, '__repeat')) {
            // Fill value for repeat element 
            $origName = substr($name, 0, - 8);
            if (isset($context[$origName])) {
                $value = $context[$origName];
            }
        }
        if ($value && (! $isPost)) {
            if ($this->valueMask) {
                return $this->maskedValue;
            } else {
                return $this->project->decrypt($value);
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
    public function saveValue($value, $isNew = false, $name = null, array $context = array())
    {
        if ($value) {
            // \MUtil_Echo::track($value);
            return $this->project->encrypt($value);
        }
    }

    /**
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return boolean
     */
    public function saveWhen($value, $isNew = false, $name = null, array $context = array())
    {
        return $value && $this->maskedValue != $value;
    }
}
