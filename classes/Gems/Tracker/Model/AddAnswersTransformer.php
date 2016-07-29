<?php

/**
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

    protected $changed = 0;

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
     * The number of item rows changed since the last save or delete
     *
     * @return int
     */
    public function getChanged()
    {
        return $this->changed;
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

    /**
     * This transform function performs the actual save (if any) of the transformer data and is called after
     * the saving of the data in the source model.
     *
     * @param \MUtil_Model_ModelAbstract $model The parent model
     * @param array $row Array containing row
     * @return array Row array containing (optionally) transformed data
     */
    public function transformRowAfterSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        $token = $this->source->getToken($row['gto_id_token']);
        $answers = $row;
        $surveyId = $this->survey->getSurveyId();
        if ($this->source->setRawTokenAnswers($token, $answers, $surveyId)) {
            $this->changed++;
        }

        // No changes
        return $row;
    }
}
