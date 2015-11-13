<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
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
     * Returns the token deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getCompletedTokenDeletionCodes()
    {
        $select = $this->_getDeletionCodeSelect();
        $select->where('grc_for_surveys = ?', self::APPLY_DO);

        return $this->db->fetchPairs($select);
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

        return $this->db->fetchPairs($select);
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

        return $this->db->fetchPairs($select);
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

        return array('' => '') + $this->db->fetchPairs($select);
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

        return $this->db->fetchPairs($select);
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

        return $this->db->fetchPairs($select);
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

        return $this->db->fetchPairs($select);
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

        return $this->db->fetchPairs($select);
    }
}
