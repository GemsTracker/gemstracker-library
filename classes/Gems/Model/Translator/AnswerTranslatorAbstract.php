<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3 24-apr-2014 16:08:57
 */
abstract class Gems_Model_Translator_AnswerTranslatorAbstract extends MUtil_Model_ModelTranslatorAbstract
{
    /**
     * Constant for creating an extra token when a token was already filled in.
     */
    const TOKEN_DOUBLE = 'double';

    /**
     * Constant for generating an error when a token does not exist or is already filled in.
     */
    const TOKEN_ERROR = 'error';

    /**
     * Constant for creating a new token, while disabling the existing token
     */
    const TOKEN_OVERWRITE = 'overwrite';

    /**
     * One of the TOKEN_ constants telling what to do when no token exists
     *
     * @var string
     */
    protected $_noToken = self::TOKEN_ERROR;

    /**
     * The Gems id of the survey to import to
     *
     * @var int
     */
    protected $_surveyId;

    /**
     * One of the TOKEN_ constants telling what to do when the token is completed
     *
     * @var string
     */
    protected $_tokenCompleted = self::TOKEN_ERROR;

    /**
     * The id of the track to import to or null
     *
     * @var int
     */
    protected $_trackId;

    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * Create an empty form for filtering and validation
     *
     * @return \MUtil_Form
     */
    protected function _createTargetForm()
    {
        return new Gems_Form();
    }

    /**
     * Add the current row to a (possibly separate) batch that does the importing.
     *
     * @param MUtil_Task_TaskBatch $importBatch The import batch to impor this row into
     * @param string $key The current iterator key
     * @param array $row translated and validated row
     * @return \MUtil_Model_ModelTranslatorAbstract (continuation pattern)
     */
    public function addSaveTask(MUtil_Task_TaskBatch $importBatch, $key, array $row)
    {
        $importBatch->setTask(
                'Import_SaveAnswerTask',
                'import-' . $key,
                $row,
                $this->getNoToken(),
                $this->getTokenCompleted()
                );

        return $this;
    }

    /**
     * If the token can be created find the respondent track for the token
     *
     * @param array $row
     * @return int|null
     */
    abstract protected function findRespondentTrackFor(array $row);

    /**
     * Find the token id using the passed row data and
     * the other translator parameters.
     *
     * @param array $row
     * @return string|null
     */
    abstract protected function findTokenFor(array $row);

    /**
     * Get information on the field translations
     *
     * @return array of fields sourceName => targetName
     * @throws MUtil_Model_ModelException
     */
    public function getFieldsTranslations()
    {
        $this->_targetModel->set('completion_date', 'label', $this->_('Completion date'),
                'order', 9,
                'type', MUtil_Model::TYPE_DATETIME
                );

        $fieldList = array('completion_date' => 'completion_date');

        foreach ($this->_targetModel->getCol('survey_question') as $name => $use) {
            if ($use) {
                $fieldList[$name] = $name;
            }
        }

        return $fieldList;
    }

    /**
     * Get the treatment when no token exists
     *
     * @return string One of the TOKEN_ constants.
     */
    public function getNoToken()
    {
        return $this->_noToken;
    }

    /**
     * Get the id of the survey to import to
     *
     * @return int $surveyId
     */
    public function getSurveyId()
    {
        return $this->_surveyId;
    }

    /**
     * Get the treatment for completed tokens
     *
     * @return string One of the TOKEN_ constants.
     */
    public function getTokenCompleted()
    {
        return $this->_tokenCompleted;
    }

    /**
     * Get the id of the track to import to or null
     *
     * @return int $trackId
     */
    public function getTrackId()
    {
        return $this->_trackId;
    }

    /**
     * Get the treatment when no token exists
     *
     * @param string $noToken One of the TOKEN_ constants.
     * @return \Gems_Model_Translator_AnswerTranslatorAbstract (continuation pattern)
     */
    public function setNoToken($noToken)
    {
        $this->_noToken = $noToken;
        return $this;
    }

    /**
     * Set the id of the survey to import to
     *
     * @param int $surveyId
     * @return \Gems_Model_Translator_AnswerTranslatorAbstract (continuation pattern)
     */
    public function setSurveyId($surveyId)
    {
        $this->_surveyId = $surveyId;
        return $this;
    }

    /**
     * Set the treatment for answered or double tokens
     *
     * @param string $tokenTreatment One f the TOKEN_ constants.
     * @return \Gems_Model_Translator_AnswerTranslatorAbstract (continuation pattern)
     */
    public function setTokenCompleted($tokenCompleted)
    {
        $this->_tokenCompleted = $tokenCompleted;
        return $this;
    }

    /**
     * Set the id of the track to import to or null
     *
     * @param int $trackId
     * @return \Gems_Model_Translator_AnswerTranslatorAbstract (continuation pattern)
     */
    public function setTrackId($trackId)
    {
        $this->_trackId = $trackId;
        return $this;
    }

    /**
     * Perform any translations necessary for the code to work
     *
     * @param mixed $row array or Traversable row
     * @param scalar $key
     * @return mixed Row array or false when errors occurred
     */
    public function translateRowValues($row, $key)
    {
        $row = parent::translateRowValues($row, $key);

        $row['track_id']  = $this->getTrackId();
        $row['survey_id'] = $this->_surveyId;
        $row['token']     = strtolower($this->findTokenFor($row));

        return $row;
    }

    /**
     * Validate the data against the target form
     *
     * @param array $row
     * @param scalar $key
     * @return mixed Row array or false when errors occurred
     */
    public function validateRowValues(array $row, $key)
    {
        $row = parent::validateRowValues($row, $key);

        $token = $this->loader->getTracker()->getToken($row['token'] ? $row['token'] : 'emptytoken');

        if ($token->exists) {
            if ($token->isCompleted() && (self::TOKEN_ERROR == $this->getTokenCompleted())) {
                $this->_addErrors(sprintf(
                        $this->_('Token %s is completed.'),
                        $token->getTokenId()
                        ), $key);
            }
        } elseif (self::TOKEN_ERROR == $this->getNoToken()) {
            $this->_addErrors(sprintf(
                    $this->_('No token found for %s.'),
                    implode(" / ", $row)
                    ), $key);
        } else {
            $respTrack = $this->findRespondentTrackFor($row);

            if ($respTrack) {
                $row['resp_track_id'] == $respTrack;
            } else {
                $this->_addErrors(sprintf(
                        $this->_('No track for inserting found for %s.'),
                        implode(" / ", $row)
                        ), $key);
            }
        }

        return $row;
    }
}
