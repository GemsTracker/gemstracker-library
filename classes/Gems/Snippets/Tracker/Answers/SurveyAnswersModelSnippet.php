<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Show all tokens of a certain survey type
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.7
 */
class Gems_Snippets_Tracker_Answers_SurveyAnswersModelSnippet extends \Gems_Tracker_Snippets_AnswerModelSnippetGeneric
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array(
        'grc_success' => SORT_DESC,
        'gto_valid_from' => SORT_ASC,
        'gto_completion_time' => SORT_ASC,
        'gto_round_order' => SORT_ASC);

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
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(\MUtil_Model_ModelAbstract $model)
    {
        if ($this->request) {
            $this->processSortOnly($model);

            if ($this->grouped) {
                $filter['gto_id_respondent']   = $this->token->getRespondentId();
                $filter['gto_id_organization'] = $this->token->getOrganizationId();
                $filter['gto_id_survey']       = $this->token->getSurveyId();
            } else {
                $filter['gto_id_token']        = $this->token->getTokenId();
            }

            $model->setFilter($filter);
        }
    }
}
