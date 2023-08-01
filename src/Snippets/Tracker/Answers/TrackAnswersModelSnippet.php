<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Answers;

use Gems\Tracker\Snippets\AnswerModelSnippetGeneric;

/**
 * Show all answers for one survey within a track
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class TrackAnswersModelSnippet extends AnswerModelSnippetGeneric
{
    /**
     * Use compact view and show all tokens of the same surveyId in
     * one view. Property used by respondent export
     *
     * @var boolean
     */
    public $grouped = true;

    public function hasHtmlOutput(): bool
    {
        $result = parent::hasHtmlOutput();
        if ($this->grouped) {
            $this->extraFilter['gto_id_respondent_track'] = $this->token->getRespondentTrackId();
            $this->extraFilter['gto_id_survey']           = $this->token->getSurveyId();
        } else {
            $this->extraFilter['gto_id_token']            = $this->token->getTokenId();
        }

        return $result;
    }
}
