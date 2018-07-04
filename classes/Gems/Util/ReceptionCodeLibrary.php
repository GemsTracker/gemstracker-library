<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Library functions and constants for working with reception codes.
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Util_ReceptionCodeLibrary extends \MUtil_Translate_TranslateableAbstract
{
    const APPLY_NOT  = 0;
    const APPLY_DO   = 1;
    const APPLY_STOP = 2;

    const REDO_NONE = 0;
    const REDO_ONLY = 1;
    const REDO_COPY = 2;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @return \Zend_Db_Select for a fetchPairs
     */
    protected function _getDeletionCodeSelect()
    {
        $select = $this->db->select();
        $select->from('gems__reception_codes', array('grc_id_reception_code', 'grc_description'))
                ->where('grc_success = 0')
                ->where('grc_active = 1')
                ->order('grc_description');

        return $select;
    }

    /**
     *
     * @return \Zend_Db_Select for a fetchPairs
     */
    protected function _getRestoreSelect()
    {
        $select = $this->db->select();
        $select->from('gems__reception_codes', array(
            'grc_id_reception_code',
            'name' => new \Zend_Db_Expr(
                    "CASE
                        WHEN grc_description IS NULL OR grc_description = '' THEN grc_id_reception_code
                        ELSE grc_description
                        END"
                    ),
            ))
                ->where('grc_success = 1')
                ->where('grc_active = 1')
                ->order('grc_description');

        return $select;
    }

    /**
     * Translate and sort while maintaining key association
     *
     * @param array $pairs
     * @return array
     */
    protected function _translateAndSort(array $pairs)
    {
        $translations = array_map([$this->translateAdapter, '_'], $pairs);

        asort($translations);

        return $translations;
    }

    /**
     * Returns the token deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getCompletedTokenDeletionCodes()
    {
        $select = $this->_getDeletionCodeSelect();
        $select->where('grc_for_surveys = ?', self::APPLY_DO);

        return $this->_translateAndSort($this->db->fetchPairs($select));
    }

    /**
     * Returns the string version of the OK code
     *
     * @return string
     */
    public function getOKString()
    {
        return \GemsEscort::RECEPTION_OK;
    }


    /**
     * Return the field values for the redo code.
     *
     * <ul><li>0: do not redo</li>
     * <li>1: redo but do not copy answers</li>
     * <li>2: redo and copy answers</li></ul>
     *
     * @staticvar array $data
     * @return array
     */
    public function getRedoValues()
    {
        static $data;

        if (! $data) {
            $data = array(
                self::REDO_NONE => $this->_('No'),
                self::REDO_ONLY => $this->_('Yes (forget answers)'),
                self::REDO_COPY => $this->_('Yes (keep answers)'));
        }

        return $data;
    }
    /**
     * Returns the string version of the skip code
     *
     * @return string
     */
    public function getSkipString()
    {
        return 'skip';
    }
    

    /**
     * Returns the string version of the Stop code
     *
     * @return string
     */
    public function getStopString()
    {
        return 'stop';
    }

    /**
     * Return the field values for surveys.
     *
     * <ul><li>0: do not use</li>
     * <li>1: use (and cascade)</li>
     * <li>2: use for open rounds only</li></ul>
     *
     * @staticvar array $data
     * @return array
     */
    public function getSurveyApplicationValues()
    {
        static $data;

        if (! $data) {
            $data = array(
                self::APPLY_NOT  => $this->_('No'),
                self::APPLY_DO   => $this->_('Yes (for individual tokens)'),
                self::APPLY_STOP => $this->_('Stop (for tokens in uncompleted tracks)'));
        }

        return $data;
    }

    /**
     * Returns the respondent deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getRespondentDeletionCodes()
    {
        $select = $this->_getDeletionCodeSelect();
        $select->where('grc_for_respondents = 1');

        return $this->_translateAndSort($this->db->fetchPairs($select));
    }

    /**
     * Returns the respondent deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getRespondentRestoreCodes()
    {
        $select = $this->_getRestoreSelect();
        $select->where('grc_for_respondents = 1');

        return $this->_translateAndSort($this->db->fetchPairs($select));
    }

    /**
     * Returns the single survey deletion reception code list.
     *
     * @return array a value => label array.
     * @deprecated since 1.7.1
     */
    public function getSingleSurveyDeletionCodes()
    {
        $select = $this->_getDeletionCodeSelect();
        $select->where('(grc_for_surveys = ? OR grc_for_tracks = 1)', self::APPLY_DO);
                //->where('grc_redo_survey = ?', self::REDO_NONE);

        return array('' => '') + $this->_translateAndSort($this->db->fetchPairs($select));
    }

    /**
     * Returns the token deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getTokenRestoreCodes()
    {
        $select = $this->_getRestoreSelect();
        $select->where('grc_for_surveys = ?', self::APPLY_DO);

        return $this->_translateAndSort($this->db->fetchPairs($select));
    }

    /**
     * Returns the track deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getTrackDeletionCodes()
    {
        $select = $this->_getDeletionCodeSelect();
        $select->where('(grc_for_tracks = 1 OR grc_for_surveys = ?)', self::APPLY_STOP);

        return $this->_translateAndSort($this->db->fetchPairs($select));
    }

    /**
     * Returns the track deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getTrackRestoreCodes()
    {
        $select = $this->_getRestoreSelect();
        $select->where('(grc_for_tracks = 1 OR grc_for_surveys = ?)', self::APPLY_STOP);

        return $this->_translateAndSort($this->db->fetchPairs($select));
    }

    /**
     * Returns the token deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getUnansweredTokenDeletionCodes()
    {
        $select = $this->_getDeletionCodeSelect();
        $select->where('grc_for_surveys = ?', self::APPLY_DO)
                ->where('grc_redo_survey = ?', self::REDO_NONE);

        return $this->_translateAndSort($this->db->fetchPairs($select));
    }
}
