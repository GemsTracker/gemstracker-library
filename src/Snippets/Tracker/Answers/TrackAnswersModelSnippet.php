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

/**
 * Show all answers for one survey within a track
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class TrackAnswersModelSnippet extends \Gems\Tracker\Snippets\AnswerModelSnippetGeneric
{
    /**
     * Use compact view and show all tokens of the same surveyId in
     * one view. Property used by respondent export
     *
     * @var boolean
     */
    public $grouped = true;

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function processFilterAndSort(\MUtil\Model\ModelAbstract $model)
    {
        if ($this->request) {
            $this->processSortOnly($model);

            if ($this->grouped) {
                $filter['gto_id_respondent_track'] = $this->token->getRespondentTrackId();
                $filter['gto_id_survey']           = $this->token->getSurveyId();
            } else {
                $filter['gto_id_token']            = $this->token->getTokenId();
            }

            $model->setFilter($filter);
        }
    }
}