<?php

/**
 *
 * @package    Gems
 * @subpackage Validate
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Validator;

/**
 *
 *
 * @package    Gems
 * @subpackage Validate
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 12, 2016 12:28:01 PM
 */
class ValidateSurveyExportCode extends \Zend_Validate_Db_Abstract
{
    /**
     * Survey id
     *
     * @var int
     */
    private $_surveyId;

    /**
     *
     * @var array Of tested survey id's
     */
    private $_tested;

    /**
     * @var array Message templates
     */
    protected $_messageTemplates = array(
        self::ERROR_NO_RECORD_FOUND => 'No record matching %value% was found.',
        self::ERROR_RECORD_FOUND    => 'A duplicate export code matching \'%value%\' was found.',
    );

    /**
     * Provides basic configuration for use with \Zend_Validate_Db Validators
     * Setting $exclude allows a single record to be excluded from matching.
     * The KeyFields are fields that occur as names in the context of the form and that
     * identify the current row - that can have the value.
     * A database adapter may optionally be supplied to avoid using the registered default adapter.
     *
     * @param int $surveyId Survey id for the current survey
     * @param \Zend_Db_Adapter_Abstract $adapter An optional database adapter to use.
     */
    public function __construct($surveyId, \Zend_Db_Adapter_Abstract $adapter = null)
    {
        $this->_surveyId = $surveyId;
        $this->_tested[] = $surveyId;

        if (! $adapter) {
            $adapter = \Zend_Db_Table_Abstract::getDefaultAdapter();
            if (null === $this->_adapter) {
                throw new \Zend_Validate_Exception('No database adapter present');
            }
        }
        $this->setAdapter($adapter);
    }

    /**
     * Gets the select object to be used by the validator.
     * If no select object was supplied to the constructor,
     * then it will auto-generate one from the given table,
     * schema, field, and adapter options.
     *
     * @return \Zend_Db_Select The Select object which will be used
     */
    public function getSelect()
    {
        if (null === $this->_select) {
            $db = $this->getAdapter();

            /**
             * Build select object
             */
            $select = new \Zend_Db_Select($db);
            $select->from('gems__surveys', array('gsu_export_code'))
                    ->where('gsu_export_code = ?')
                    ->where('gsu_id_survey NOT IN (?)', implode(', ', $this->_tested))
                    ->limit(1);

            // \MUtil\EchoOut\EchoOut::track($select->__toString());
            $this->_select = $select;
        }
        return $this->_select;
    }

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @param  array $context
     * @return boolean
     * @throws \Zend_Validate_Exception If validation of $value is impossible
     */
    public function isValid($value, $context = array())
    {
        $this->_setValue($value);

        foreach ($context as $field => $val) {
            if (\MUtil\StringUtil\StringUtil::startsWith($field, 'survey__')) {
                $sid = intval(substr($field, 8));
                if (($sid !== $this->_surveyId) && ($value == $val)) {
                    $this->_error(self::ERROR_RECORD_FOUND);
                    return false;
                }
                $this->_tested[] = $sid;
            }
        }

        $result = $this->_query($value);
        if ($result) {
            $this->_error(self::ERROR_RECORD_FOUND);
            return false;
        }

        return true;
    }
}
