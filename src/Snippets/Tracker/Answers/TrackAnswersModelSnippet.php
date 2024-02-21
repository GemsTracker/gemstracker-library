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
use Zalt\Model\MetaModelInterface;

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
     * @var bool
     */
    public bool $grouped = true;

    public function getFilter(MetaModelInterface $metaModel): array
    {
        $filter =  parent::getFilter($metaModel);
        if ($this->grouped) {
            $filter['gto_id_respondent_track'] = $this->token->getRespondentTrackId();
            $filter['gto_id_survey'] = $this->token->getSurveyId();
            if (isset($filter['gto_id_token'])) {
                unset($filter['gto_id_token']);
            }
        }
        return $filter;
    }
}
