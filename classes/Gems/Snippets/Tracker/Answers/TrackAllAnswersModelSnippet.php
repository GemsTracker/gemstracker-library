<?php

/**
 *
 * @package    Gems
 * @subpackage TrackAllAnswersSnippet
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage TrackAllAnswersSnippet
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class Gems_Snippets_Tracker_Answers_TrackAllAnswersModelSnippet extends \Gems_Tracker_Snippets_AnswerModelSnippetGeneric
{
    /**
     * Use compact view and show all tokens of the same surveyId in
     * one view. Property used by respondent export
     *
     * @var boolean
     */
    public $grouped = true;

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = parent::createModel();

        foreach ($model->getItemNames() as $name) {
            if (! $model->has($name, 'label')) {
                $model->set($name, 'label', ' ');
            }
        }

        return $model;
    }

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
                $filter['gto_id_respondent_track'] = $this->token->getRespondentTrackId();
                $filter['gto_id_survey']           = $this->token->getSurveyId();
            } else {
                $filter['gto_id_token']            = $this->token->getTokenId();
            }

            $model->setFilter($filter);
        }
    }
}
