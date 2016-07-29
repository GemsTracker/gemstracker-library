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
class Gems_Model_Translator_StraightTranslator extends \MUtil_Model_Translator_StraightTranslator
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * The name of the field to store the organization id in
     *
     * @var string
     */
    protected $orgIdField = 'gr2o_id_organization';

    /**
     * Extra values the origanization id field accepts
     *
     *
     * @var array
     */
    protected $orgTranslations;

    /**
     * Create an empty form for filtering and validation
     *
     * @return \MUtil_Form
     */
    protected function _createTargetForm()
    {
        return new \Gems_Form();
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->orgTranslations = $this->db->fetchPairs('
            SELECT gor_provider_id, gor_id_organization
                FROM gems__organizations
                WHERE gor_provider_id IS NOT NULL
                ORDER BY gor_provider_id');

        $this->orgTranslations = $this->orgTranslations + $this->db->fetchPairs('
            SELECT gor_code, gor_id_organization
                FROM gems__organizations
                WHERE gor_code IS NOT NULL
                ORDER BY gor_id_organization');
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return ($this->db instanceof \Zend_Db_Adapter_Abstract) &&
            parent::checkRegistryRequestsAnswers();
    }

    /**
     * Perform any translations necessary for the code to work
     *
     * @param mixed $row array or \Traversable row
     * @param scalar $key
     * @return mixed Row array or false when errors occurred
     */
    public function translateRowValues($row, $key)
    {
        $row = parent::translateRowValues($row, $key);

        if (! $row) {
            return false;
        }

        // Get the real organization from the provider_id or code if it exists
        if (isset($row[$this->orgIdField], $this->orgTranslations[$row[$this->orgIdField]])) {
            $row[$this->orgIdField] = $this->orgTranslations[$row[$this->orgIdField]];
        }

        return $row;
    }
}
