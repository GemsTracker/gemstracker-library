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
 * @subpackage Ytil
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Utility function for the user of reception codes.
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Util_ReceptionCode extends Gems_Registry_CachedArrayTargetAbstract
{
    /**
     * Variable to add tags to the cache for cleanup.
     *
     * @var array
     */
    protected $_cacheTags = array('reception_code');

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

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
        return Gems_Util_ReceptionCodeLibrary::REDO_COPY == $this->_get('grc_redo_survey');
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
        return $this->_get('grc_for_surveys') > Gems_Util_ReceptionCodeLibrary::APPLY_NOT;
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
        // MUtil_Echo::track($this->_data);
        return $this->_get('grc_for_surveys') === Gems_Util_ReceptionCodeLibrary::APPLY_STOP;
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
