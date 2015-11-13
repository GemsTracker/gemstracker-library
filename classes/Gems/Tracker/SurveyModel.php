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
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

use Gems\Tracker\Model\AddAnswersTransformer;

/**
 * More correctly a Survey ANSWERS Model as it adds answers to token information/
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_SurveyModel extends \Gems_Model_JoinModel
{
    /**
     * Constant containing css classname for main questions
     */
    const CLASS_MAIN_QUESTION = 'question';

    /**
     * Constant containing css classname for subquestions
     */
    const CLASS_SUB_QUESTION  = 'question_sub';

    /**
     *
     * @var \Gems_Tracker_Source_SourceInterface
     */
    protected $source;

    /**
     *
     * @var \Gems_Tracker_Survey
     */
    protected $survey;

    public function getSurvey()
    {
        return $this->survey;
    }

    public function __construct(\Gems_Tracker_Survey $survey, \Gems_Tracker_Source_SourceInterface $source)
    {
        parent::__construct($survey->getName(), 'gems__tokens', 'gto');
        $this->addTable('gems__reception_codes',  array('gto_reception_code' => 'grc_id_reception_code'));

        $this->addColumn(
            'CASE WHEN grc_success = 1 AND gto_valid_from <= CURRENT_TIMESTAMP AND gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) THEN 1 ELSE 0 END',
            'can_be_taken');
        $this->addColumn(
            "CASE WHEN grc_success = 1 THEN '' ELSE 'deleted' END",
            'row_class');

        $this->source = $source;
        $this->survey = $survey;
        $this->addAnswersToModel();
    }

    /**
     * Returns a nested array containing the items requested.
     *
     * @param array $filter Filter array, num keys contain fixed expresions, text keys are equal or one of filters
     * @param array $sort Sort array field name => sort type
     * @return array Nested array or false
     */
    /*protected function _load(array $filter, array $sort)
    {
        return $this->addAnswers(parent::_load($filter, $sort));
    }*/

    /**
     * Returns a nested array containing the items requested, including answers.
     *
     * @param array $inputRows Nested rows with Gems token information
     * @return array Nested array or false
     */
    protected function addAnswers(array $inputRows)
    {
        $resultRows = $inputRows;
        $tokens = \MUtil_Ra::column('gto_id_token', $inputRows);

        // \MUtil_Echo::track($tokens);
        /*$answerRows = $this->source->getRawTokenAnswerRows(array('token' => $tokens), $this->survey->getSurveyId());
        $emptyRow   = array_fill_keys($this->getItemNames(), null);
        $resultRows = array();

        foreach ($inputRows as $row) {
            $tokenId = $row['gto_id_token'];

            if (isset($answerRows[$tokenId])) {
                $resultRows[$tokenId] = $row + $answerRows[$tokenId] + $emptyRow;
            } else {
                $resultRows[$tokenId] = $row + $emptyRow;
            }
        }*/
        return $resultRows;
    }

    protected function addAnswersToModel()
    {
        $transformer = new AddAnswersTransformer($this->survey, $this->source);
        $this->addTransformer($transformer);
    }

    /**
     * True if this model allows the creation of new model items.
     *
     * @return boolean
     */
    public function hasNew()
    {
        return false;
    }

    /**
     * Returns an array containing the first requested item.
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @param mixed $sort True to use the stored sort, array to specify a different sort
     * @return array An array or false
     */
    /*public function loadFirst($filter = true, $sort = true)
    {
        if ($firstResult = parent::loadFirst($filter, $sort)) {
            $result = $this->addAnswers(array($firstResult));
        } else {
            $result = array();
        }
        return reset($result);
    }*/

    /**
     * Returns a \Traversable spewing out arrays containing the items requested.
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @param mixed $sort True to use the stored sort, array to specify a different sort
     * @return \Traversable
     */
    /*public function loadIterator($filter = true, $sort = true)
    {
        return $this->addAnswers(parent::loadIterator($filter, $sort));
    }*/

    /**
     * Returns a \Zend_Paginator for the items in the model
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @param mixed $sort True to use the stored sort, array to specify a different sort
     * @return \Zend_Paginator
     */
    /*public function loadPaginator($filter = true, $sort = true)
    {
        // Do not use a select paginator for the moment, till we can add addAnswers()
        return \Zend_Paginator::factory($this->load($filter, $sort));
    }*/
}
