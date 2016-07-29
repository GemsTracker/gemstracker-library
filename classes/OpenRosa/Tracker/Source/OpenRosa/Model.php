<?php

/**
 * @package    Gems
 * @subpackage OpenRosa
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Model.php 223 2011-12-19 09:48:15Z 175780 $
 */

/**
 * More correctly a Survey ANSWERS Model as it adds answers to token information
 *
 * @package    Gems
 * @subpackage OpenRosa
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class OpenRosa_Tracker_Source_OpenRosa_Model extends \Gems_Tracker_SurveyModel
{
    public function addAnswers(array $inputRows)
    {
        $tokens = \MUtil_Ra::column('gto_id_token', $inputRows);

        $answerRows = $this->source->getRawTokenAnswerRows(array('token' => $tokens), $this->survey->getSurveyId());
        $emptyRow   = array_fill_keys($this->getItemNames(), null);
        $resultRows = array();

        $answerTokens = \MUtil_Ra::column('token', $answerRows);

        foreach ($inputRows as $row) {
            $tokenId = $row['gto_id_token'];
            $idx = array_search($tokenId, $answerTokens);
            if ($idx !== false && isset($answerRows[$idx])) {
                $resultRows[$tokenId] = $row + $answerRows[$idx] + $emptyRow;
            } else {
                $resultRows[$tokenId] = $row + $emptyRow;
            }
        }
        return $resultRows;
    }
}