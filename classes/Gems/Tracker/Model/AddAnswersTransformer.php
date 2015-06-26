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
 * @subpackage Tracker_Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AddTrackFieldsTransformer.php 2534 2015-05-05 18:07:37Z matijsdejong $
 */

namespace Gems\Tracker\Model;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3 13-feb-2014 16:33:25
 */
class AddAnswersTransformer extends \MUtil_Model_ModelTransformerAbstract
{

    /**
     * @var \Gems_Tracker_Source_SourceInterface
     */
    protected $source;

    /**
     * @var \Gems_Tracker_Survey
     */
    protected $survey;

    /**
     * @var string The field in the suppied data array that holds the Token ID
     */
    protected $tokenField = 'gto_id_token';

    public function __construct(\Gems_Tracker_Survey $survey, \Gems_Tracker_Source_SourceInterface $source)
    {
        $this->survey = $survey;
        $this->source = $source;
    }

    /**
     * The transform function performs the actual transformation of the data and is called after
     * the loading of the data in the source model.
     *
     * @param \MUtil_Model_ModelAbstract $model The parent model
     * @param array $data Nested array
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array Nested array containing (optionally) transformed data
     */
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        // get tokens
        
        $tokens = \MUtil_Ra::column('gto_id_token', $data);

        $answerRows = $this->source->getRawTokenAnswerRows(array('token' => $tokens), $this->survey->getSurveyId());
        $resultRows = array();
        
        $emptyRow = array_fill_keys($model->getItemNames(), null);

        foreach ($data as $row) {
            $tokenId = $row['gto_id_token'];

            if (isset($answerRows[$tokenId])) {
                $resultRows[$tokenId] = $row + $answerRows[$tokenId] + $emptyRow;
            } else {
                $resultRows[$tokenId] = $row + $emptyRow;
            }
        }

        //\MUtil_Echo::track($tokens);

        //\MUtil_Echo::track($resultRows);

        // No changes
        return $resultRows;
    }
}
