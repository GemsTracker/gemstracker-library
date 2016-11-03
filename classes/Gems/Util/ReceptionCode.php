<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

use MUtil\Translate\TranslateableTrait;

/**
 * Utility function for the use of reception codes.
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Util_ReceptionCode extends \Gems_Registry_CachedArrayTargetAbstract
{
    use TranslateableTrait;

    /**
     * Variable to add tags to the cache for cleanup.
     *
     * @var array
     */
    protected $_cacheTags = array('receptionCode');

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Compatibility mode, for use with logical operators returns this->getCode()
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getCode();
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * This function is no needed if the classes are setup correctly
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->initTranslateable();
    }

    /**
     * Returns the complete record.
     *
     * @return array
     */
    public function getAllData()
    {
        return $this->_data;
    }

    /**
     * The reception code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->_id;
    }

    /**
     *
     * @return boolean
     */
    public function getDescription()
    {
        return $this->_($this->_get('grc_description'));
    }

    /**
     *
     * @return boolean
     */
    public function hasDescription()
    {
        return (boolean) $this->_get('grc_description');
    }

    /**
     *
     * @return boolean
     */
    public function hasRedoCode()
    {
        return (boolean) $this->_get('grc_redo_survey');
    }

    /**
     * True if the reception code is a redo survey copy.
     *
     * @return boolean
     */
    public function hasRedoCopyCode()
    {
        return \Gems_Util_ReceptionCodeLibrary::REDO_COPY == $this->_get('grc_redo_survey');
    }

    /**
     * Is this code for respondent use?
     *
     * @return boolean
     */
    public function isForRespondents()
    {
        return (boolean) $this->_get('grc_for_respondents');
    }

    /**
     * Is this code for track use?
     *
     * @return boolean
     */
    public function isForTracks()
    {
        return (boolean) $this->_get('grc_for_tracks');
    }

    /**
     * Is this code for survey use?
     *
     * @return boolean
     */
    public function isForSurveys()
    {
        return $this->_get('grc_for_surveys') > \Gems_Util_ReceptionCodeLibrary::APPLY_NOT;
    }

    /**
     * Does this code overwrite set values?
     *
     * @return boolean
     */
    public function isOverwriter()
    {
        return (boolean) $this->_get('grc_overwrite_answers');
    }

    /**
     * Is this code a survey stop code.
     *
     * Then do not apply it to the track or respondent, but do apply it to the tokens.
     *
     * @return boolean
     */
    public function isStopCode()
    {
        // \MUtil_Echo::track($this->_data);
        return $this->_get('grc_for_surveys') === \Gems_Util_ReceptionCodeLibrary::APPLY_STOP;
    }

    /**
     * Is this code a success code.
     *
     * @return boolean
     */
    public function isSuccess()
    {
        return (boolean) $this->_get('grc_success');
    }

    /**
     * Load the data when the cache is empty.
     *
     * @param mixed $id
     * @return array The array of data values
     */
    protected function loadData($id)
    {
        $sql = "SELECT * FROM gems__reception_codes WHERE grc_id_reception_code = ? LIMIT 1";
        return $this->db->fetchRow($sql, $id);
    }
}
